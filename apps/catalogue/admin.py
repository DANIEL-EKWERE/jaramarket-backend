from django.contrib import admin
from .models import (Category, CategoryProduct, CategoryType, CategoryUser,
                     Ingredient, IngredientProduct, Product, Step, Uom)


class IngredientProductInline(admin.TabularInline):
    model = IngredientProduct
    extra = 1
    autocomplete_fields = ["ingredient"]
    fields = ["ingredient", "quantity", "unit"]


class CategoryProductInline(admin.TabularInline):
    model = CategoryProduct
    extra = 1
    fields = ["category"]


@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    list_display = ["id", "name", "price", "discount_price", "stock", "rating"]
    search_fields = ["name", "description"]
    list_filter = ["categories"]
    inlines = [CategoryProductInline, IngredientProductInline]


@admin.register(Ingredient)
class IngredientAdmin(admin.ModelAdmin):
    list_display = ["id", "name", "category", "unit", "price", "stock"]
    search_fields = ["name"]
    list_filter = ["category"]


admin.site.register(CategoryType)
admin.site.register(Category)
admin.site.register(Uom)
