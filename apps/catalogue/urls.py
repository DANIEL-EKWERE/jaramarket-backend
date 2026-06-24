from django.urls import path
from . import views as v

urlpatterns = [
    path("vendors/categories", v.vendor_categories),
    path("fetch/categories-all-products", v.categories_all_products),
    path("fetch/categories-limit-products", v.categories_limit_products),
    path("fetch/ingredients", v.fetch_ingredients),
    path("fetch/product", v.fetch_product),
    path("fetch/uom", v.fetch_uom),
    path("fetch/product/<int:id>", v.get_product_by_id),
    path("advertisements", v.fetch_adverts),
    path("foods", v.foods_store),
    path("foods/<int:id>", v.foods_update),
    path("fetch-ProductCategory", v.categories_all_products),
]
