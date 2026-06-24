"""
Backwards-compatibility re-export. All models now live in their domain apps.
`from api.models import X` still works via this shim.
"""
from .base import AllObjectsManager, SoftDeleteManager, SoftDeleteModel, SoftDeleteQuerySet, TimestampedModel
from apps.geo.models import Country, Lga, State
from apps.accounts.models import Admin, AdminPermission, Permission, Roles, User, UserManager, UserOtp, UserPermission
from apps.catalogue.models import (
    Category, CategoryProduct, CategoryType, CategoryUser,
    Ingredient, IngredientLgaPrice, IngredientProduct,
    IngredientStatePrice, Product, ProductStatePrice, Step, Uom,
)
from apps.customers.models import Address, Cart, CartItem, Favorite
from apps.orders.models import Order, OrderItem, OrderItemLog
from apps.vendors.models import Franchise, StateRepresentative, Vendor
from apps.finance.models import Bank, BankAccount, Commission, PaymentLog, PaymentNow, TransactionLog, Transfer, Wallet
from apps.support.models import Advertisement, HelpTicket, Notification, Setting, Support
