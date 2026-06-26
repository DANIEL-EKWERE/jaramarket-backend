from django.contrib import admin
from .models import (Category, CategoryProduct, CategoryType, CategoryUser,
                     Ingredient, IngredientLgaPrice, IngredientProduct,
                     IngredientStatePrice, Product, ProductStatePrice, Step, Uom)


class IngredientProductInline(admin.TabularInline):
    model = IngredientProduct
    extra = 1
    autocomplete_fields = ["ingredient"]
    fields = ["ingredient", "quantity", "unit"]


class CategoryProductInline(admin.TabularInline):
    model = CategoryProduct
    extra = 1
    fields = ["category"]


class ProductStatePriceInline(admin.TabularInline):
    model = ProductStatePrice
    extra = 1
    fields = ["state", "price", "discount_price"]


class IngredientStatePriceInline(admin.TabularInline):
    model = IngredientStatePrice
    extra = 1
    fields = ["state", "price", "discounted_price"]


class IngredientLgaPriceInline(admin.TabularInline):
    model = IngredientLgaPrice
    extra = 1
    fields = ["lga", "price", "discounted_price"]


@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    list_display = ["id", "name", "price", "discount_price", "stock", "rating"]
    search_fields = ["name", "description"]
    list_filter = ["categories"]
    inlines = [CategoryProductInline, IngredientProductInline, ProductStatePriceInline]


@admin.register(Ingredient)
class IngredientAdmin(admin.ModelAdmin):
    list_display = ["id", "name", "category", "unit", "price", "stock"]
    search_fields = ["name"]
    list_filter = ["category"]
    inlines = [IngredientStatePriceInline, IngredientLgaPriceInline]


admin.site.register(CategoryType)
admin.site.register(Category)
admin.site.register(Uom)
