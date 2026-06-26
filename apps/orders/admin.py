from django.contrib import admin
from django.utils import timezone
from .models import Order, OrderItem, OrderItemLog


class OrderItemInline(admin.TabularInline):
    model = OrderItem
    extra = 1
    fields = ["product", "ingredient", "quantity", "price", "unit", "vendor", "amount", "vendor_amount", "commision", "status"]
    autocomplete_fields = ["product", "ingredient", "vendor"]


@admin.register(Order)
class OrderAdmin(admin.ModelAdmin):
    list_display  = ["id", "user", "status", "delivery_type", "total", "order_date", "created_at"]
    list_filter   = ["status", "delivery_type"]
    search_fields = ["user__firstname", "user__lastname", "user__email", "reference"]
    readonly_fields = ["created_at", "updated_at"]
    inlines = [OrderItemInline]

    fieldsets = [
        ("Order Info", {
            "fields": ["user", "order_date", "delivery_type", "address", "status", "reference"]
        }),
        ("Financials", {
            "fields": ["total", "shipping_fee", "service_charge", "vat"]
        }),
        ("Notes", {
            "fields": ["remarks", "meal_prep", "audio"]
        }),
        ("Timestamps", {
            "fields": ["created_at", "updated_at"],
            "classes": ["collapse"],
        }),
    ]

    def save_model(self, request, obj, form, change):
        if not change and not obj.order_date:
            obj.order_date = timezone.now()
        super().save_model(request, obj, form, change)

    def get_form(self, request, obj=None, **kwargs):
        form = super().get_form(request, obj, **kwargs)
        if "order_date" in form.base_fields:
            form.base_fields["order_date"].required = False
            form.base_fields["order_date"].initial  = timezone.now
        return form


@admin.register(OrderItem)
class OrderItemAdmin(admin.ModelAdmin):
    list_display  = ["id", "order", "product", "ingredient", "quantity", "price", "status", "vendor"]
    list_filter   = ["status"]
    search_fields = ["order__id", "product__name", "ingredient__name", "vendor__name"]
    autocomplete_fields = ["product", "ingredient", "vendor", "order"]
    readonly_fields = ["created_at", "updated_at"]


@admin.register(OrderItemLog)
class OrderItemLogAdmin(admin.ModelAdmin):
    list_display = ["id", "order_item", "vendor", "status", "changed_at"]
    list_filter  = ["status"]
    readonly_fields = ["changed_at"]
