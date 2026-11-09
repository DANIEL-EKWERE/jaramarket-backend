from unittest.mock import patch

from django.test import TestCase, override_settings
from django.utils import timezone
from rest_framework.test import APIClient

from api.services._base import USER_TYPE, issue_tokens
from api.services.dispatch import MarketDispatchService
from apps.catalogue.models import Category
from apps.customers.models import Address, Favorite
from apps.finance.models import BankAccount, Transfer, Wallet
from apps.orders.models import MarketOfferResponse, Order, OrderItem, OrderItemMarketAttempt
from apps.vendors.models import Market, Vendor
from .models import Roles, User, UserOtp

URL = "/api/jaram/delete-account"


class DeleteAccountTests(TestCase):
    def setUp(self):
        self.user = User.objects.create_user(
            "ada@example.com", "secret123", firstname="Ada", lastname="Obi",
            phone_number="08011112222", pin="1234", fcm_token="tok",
            role=Roles.CUSTOMER, is_active=True)
        Wallet.objects.create(user=self.user, balance=500)
        self.client = APIClient()
        self.token = issue_tokens(self.user)["access_token"]
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {self.token}")

    def _order(self, status, address=None):
        return Order.objects.create(user=self.user, order_date=timezone.now(),
                                    delivery_type="delivery", total=1000,
                                    status=status, address=address)

    def test_requires_authentication(self):
        self.assertEqual(APIClient().post(URL).status_code, 401)

    def test_deletes_personal_data_and_keeps_order_history(self):
        used = Address.objects.create(user=self.user, contact_address="1 Allen Ave",
                                      phone_number="0801", is_default=True)
        Address.objects.create(user=self.user, contact_address="2 Unused Rd")
        Favorite.objects.create(user=self.user)
        order = self._order("completed", address=used)

        res = self.client.post(URL)
        self.assertEqual(res.status_code, 200, res.content)

        user = User.objects.get(id=self.user.id)
        self.assertFalse(user.is_active)
        self.assertIsNotNone(user.deleted_at)
        self.assertEqual((user.firstname, user.lastname), ("Deleted", "User"))
        self.assertNotEqual(user.email, "ada@example.com")
        self.assertFalse(user.has_usable_password())
        self.assertIsNone(user.phone_number)
        self.assertIsNone(user.pin)
        self.assertIsNone(user.fcm_token)

        # Only the address on the past order survives, and it's blanked.
        self.assertEqual(list(user.addresses.values_list("id", flat=True)), [used.id])
        used.refresh_from_db()
        self.assertIsNone(used.contact_address)
        self.assertIsNone(used.phone_number)
        self.assertFalse(user.favorites.exists())
        self.assertTrue(Order.objects.filter(id=order.id).exists())
        self.assertTrue(Wallet.objects.filter(user=user).exists())

    def test_old_token_stops_working(self):
        self.client.post(URL)
        self.assertEqual(self.client.get("/api/jaram/fetch-user").status_code, 401)

    def test_email_can_register_again(self):
        self.client.post(URL)
        self.assertFalse(User.objects.filter(email="ada@example.com").exists())

    def test_blocked_while_an_order_is_in_progress(self):
        self._order("in_transit")
        res = self.client.post(URL)
        self.assertEqual(res.status_code, 422)
        self.assertTrue(User.objects.get(id=self.user.id).is_active)

    def test_staff_accounts_cannot_use_it(self):
        self.user.role = Roles.LOGISTICS
        self.user.save()
        self.assertEqual(self.client.post(URL).status_code, 403)


class DeleteVendorAccountTests(TestCase):
    def setUp(self):
        self.vendor = User.objects.create_user(
            "shop@example.com", "secret123", firstname="Bola", lastname="Ade",
            business_name="Bola Foods", account_number="0123456789",
            recipient_code="RCP_1", role=Roles.VENDOR, is_active=True)
        self.wallet = Wallet.objects.create(user=self.vendor, balance=0)
        self.market = Market.objects.create(name="Mile 12", latitude=6.6, longitude=3.4)
        self.profile = Vendor.objects.create(user=self.vendor, market=self.market)
        self.category = Category.objects.create(name="Grains")
        self.vendor.categories.add(self.category)
        customer = User.objects.create_user(
            "buyer@example.com", "secret123", firstname="Ada", lastname="Obi",
            role=Roles.CUSTOMER, is_active=True)
        self.order = Order.objects.create(user=customer, order_date=timezone.now(),
                                          delivery_type="delivery", total=1000)
        self.client = APIClient()
        token = issue_tokens(self.vendor)["access_token"]
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {token}")

    def _item(self, status, vendor=None):
        return OrderItem.objects.create(order=self.order, vendor=vendor or self.vendor,
                                        status=status, quantity=1, price=1000)

    def _assert_blocked(self):
        res = self.client.post(URL)
        self.assertEqual(res.status_code, 422, res.content)
        self.assertTrue(User.objects.get(id=self.vendor.id).is_active)

    def test_deletes_vendor_and_takes_them_out_of_dispatch(self):
        BankAccount.objects.create(owner_type=USER_TYPE, owner_id=self.vendor.id,
                                   bank_code="058", account_number="0123456789",
                                   bank_name="GTB", account_name="Bola Ade")
        past = self._item("completed")

        res = self.client.post(URL)
        self.assertEqual(res.status_code, 200, res.content)

        user = User.objects.get(id=self.vendor.id)
        self.assertFalse(user.is_active)
        self.assertNotEqual(user.email, "shop@example.com")
        self.assertIsNone(user.business_name)
        self.assertIsNone(user.account_number)
        self.assertFalse(user.categories.exists())
        self.assertFalse(BankAccount.objects.filter(owner_id=user.id).exists())
        self.profile.refresh_from_db()
        self.assertFalse(self.profile.is_active)

        # Their order history stays for the customer and for finance.
        past.refresh_from_db()
        self.assertEqual(past.vendor_id, user.id)
        self.assertFalse(MarketDispatchService().eligible_vendors(
            self.market, self.category.id).exists())

    def test_blocked_while_an_accepted_item_is_undelivered(self):
        self._item("processing")
        self._assert_blocked()

    def test_blocked_while_a_delivered_item_is_awaiting_payout(self):
        # Vendors are paid when QA completes the order, after they deliver.
        self._item("delivered")
        self._assert_blocked()

    def test_blocked_while_wallet_has_a_balance(self):
        self.wallet.balance = 100
        self.wallet.save()
        self._assert_blocked()

    def test_blocked_while_a_withdrawal_could_still_be_refunded(self):
        Transfer.objects.create(reference="TRF1", recipient_code="RCP_1", amount=100,
                                owner_type=USER_TYPE, owner_id=self.vendor.id,
                                bank_code="058", account_number="0123456789",
                                bank_name="GTB", account_name="Bola Ade", status="pending")
        self._assert_blocked()

    def test_finished_withdrawals_do_not_block(self):
        Transfer.objects.create(reference="TRF2", recipient_code="RCP_1", amount=100,
                                owner_type=USER_TYPE, owner_id=self.vendor.id,
                                bank_code="058", account_number="0123456789",
                                bank_name="GTB", account_name="Bola Ade", status="success")
        self.assertEqual(self.client.post(URL).status_code, 200)

    def test_open_offers_are_declined(self):
        item = self._item("offered", vendor=None)
        item.vendor = None
        item.save()
        attempt = OrderItemMarketAttempt.objects.create(
            order_item=item, market=self.market, status="offered")
        response = MarketOfferResponse.objects.create(attempt=attempt, vendor=self.vendor)

        self.assertEqual(self.client.post(URL).status_code, 200)
        response.refresh_from_db()
        self.assertEqual(response.decision, "declined")


class SelfAssignedRoleTests(TestCase):
    """Nobody may choose a staff role for themselves when signing up."""

    def setUp(self):
        self.client = APIClient()

    def _register(self, role):
        with patch("api.services.auth.UserRegistrationService.send_otp"):
            return self.client.post("/api/jaram/register", {
                "firstname": "Eve", "lastname": "X", "email": "eve@example.com",
                "password": "secret123", "role": role}, format="json")

    def _google(self, email, role):
        with override_settings(GOOGLE_CLIENT_IDS="test-client"), patch(
                "google.oauth2.id_token.verify_oauth2_token",
                return_value={"email": email, "given_name": "Eve", "family_name": "X"}):
            return self.client.post("/api/jaram/google-signin",
                                    {"id_token": "t", "role": role}, format="json")

    def test_register_rejects_staff_roles(self):
        for role in (Roles.ADMIN, Roles.SUPER_ADMIN, Roles.ACCOUNTS, Roles.LOGISTICS):
            self.assertEqual(self._register(role).status_code, 400, role)
        self.assertFalse(User.objects.filter(email="eve@example.com").exists())

    def test_register_still_accepts_customer_and_vendor(self):
        res = self._register(Roles.VENDOR)
        self.assertEqual(res.status_code, 201, res.content)
        self.assertEqual(User.objects.get(email="eve@example.com").role, Roles.VENDOR)

    def test_google_rejects_staff_roles(self):
        res = self._google("eve@example.com", Roles.SUPER_ADMIN)
        self.assertEqual(res.status_code, 422, res.content)
        self.assertFalse(User.objects.filter(email="eve@example.com").exists())

    def test_google_creates_vendor(self):
        res = self._google("eve@example.com", Roles.VENDOR)
        self.assertEqual(res.status_code, 201, res.content)
        self.assertEqual(res.json()["data"]["role"], Roles.VENDOR)

    def test_existing_customer_is_not_upgraded_by_the_vendor_app(self):
        User.objects.create_user("ada@example.com", "secret123", firstname="Ada",
                                 lastname="Obi", role=Roles.CUSTOMER, is_active=True)
        res = self._google("ada@example.com", Roles.VENDOR)
        self.assertEqual(res.status_code, 200, res.content)
        self.assertEqual(res.json()["data"]["role"], Roles.CUSTOMER)
        self.assertEqual(User.objects.get(email="ada@example.com").role, Roles.CUSTOMER)

    def test_customer_token_is_rejected_on_vendor_endpoints(self):
        customer = User.objects.create_user(
            "ada@example.com", "secret123", firstname="Ada", lastname="Obi",
            role=Roles.CUSTOMER, is_active=True)
        token = issue_tokens(customer)["access_token"]
        self.client.credentials(HTTP_AUTHORIZATION=f"Bearer {token}")
        res = self.client.get("/api/jaram/vendor/shop-profile")
        self.assertEqual(res.status_code, 403, res.content)


class SuspendedAccountTests(TestCase):
    """An admin suspension (inactive after the email was verified) must stick:
    no sign-in route may switch the account back on."""

    def setUp(self):
        self.client = APIClient()
        self.user = User.objects.create_user(
            "sus@example.com", "secret123", firstname="Sus", lastname="Pended",
            role=Roles.VENDOR, is_active=False, email_verified_at=timezone.now())

    def _google(self, email="sus@example.com"):
        with override_settings(GOOGLE_CLIENT_IDS="test-client"), patch(
                "google.oauth2.id_token.verify_oauth2_token",
                return_value={"email": email, "given_name": "Sus", "family_name": "P"}):
            return self.client.post("/api/jaram/google-signin",
                                    {"id_token": "t", "role": Roles.VENDOR}, format="json")

    def _still_inactive(self):
        return not User.objects.get(id=self.user.id).is_active

    def test_google_does_not_reactivate_a_suspended_account(self):
        res = self._google()
        self.assertEqual(res.status_code, 403, res.content)
        self.assertNotIn("token", res.json().get("data") or {})
        self.assertTrue(self._still_inactive())

    def test_otp_does_not_reactivate_a_suspended_account(self):
        UserOtp.objects.create(email="sus@example.com", otp="1234")
        for path in ("/api/jaram/validate-otp", "/api/jaram/validate-email"):
            res = self.client.post(path, {"email": "sus@example.com", "otp": "1234"},
                                   format="json")
            self.assertEqual(res.status_code, 400, (path, res.content))
        self.assertTrue(self._still_inactive())

    def test_password_login_says_suspended(self):
        res = self.client.post("/api/jaram/login",
                               {"email": "sus@example.com", "password": "secret123"},
                               format="json")
        self.assertNotEqual(res.status_code, 200)
        self.assertIn("suspended", res.content.decode().lower())

    def test_google_does_not_revive_an_admin_deleted_account(self):
        # The dashboard's delete keeps the email, unlike in-app deletion.
        self.user.is_active = False
        self.user.deleted_at = timezone.now()
        self.user.save()
        self.assertEqual(self._google().status_code, 403)
        self.assertTrue(self._still_inactive())

    def test_google_finishes_an_unfinished_signup(self):
        # Registered but never entered the OTP: Google has now verified the email.
        self.user.email_verified_at = None
        self.user.save()
        res = self._google()
        self.assertEqual(res.status_code, 200, res.content)
        user = User.objects.get(id=self.user.id)
        self.assertTrue(user.is_active)
        self.assertIsNotNone(user.email_verified_at)

    def test_unsuspended_account_can_sign_in_again(self):
        self.user.is_active = True
        self.user.save()
        self.assertEqual(self._google().status_code, 200)
