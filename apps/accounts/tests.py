from django.test import TestCase
from django.utils import timezone
from rest_framework.test import APIClient

from api.services._base import issue_tokens
from apps.customers.models import Address, Favorite
from apps.finance.models import Wallet
from apps.orders.models import Order
from .models import Roles, User

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

    def test_non_customers_cannot_use_it(self):
        self.user.role = Roles.VENDOR
        self.user.save()
        self.assertEqual(self.client.post(URL).status_code, 403)
