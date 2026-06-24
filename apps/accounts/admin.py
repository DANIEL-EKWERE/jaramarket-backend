from django.contrib import admin

from .models import Admin, AdminPermission, Permission, User, UserOtp, UserPermission

# User is registered with a full UserAdmin in api/admin.py which runs after this.
# Register it simply here so api/admin.py can unregister and replace it.
admin.site.register(User)


@admin.register(Permission)
class PermissionAdmin(admin.ModelAdmin):
    list_display = ("id", "name", "slug", "group")
    search_fields = ("name", "slug")


@admin.register(UserPermission)
class UserPermissionAdmin(admin.ModelAdmin):
    list_display = ("id", "user", "permission")
    search_fields = ("user__email",)


@admin.register(Admin)
class AdminModelAdmin(admin.ModelAdmin):
    list_display = ("id", "name", "email", "is_active", "last_login_at")
    search_fields = ("name", "email")


@admin.register(AdminPermission)
class AdminPermissionAdmin(admin.ModelAdmin):
    list_display = ("id", "admin", "permission")


@admin.register(UserOtp)
class UserOtpAdmin(admin.ModelAdmin):
    list_display = ("id", "email", "otp", "created_at")
    search_fields = ("email",)
