from django.contrib import admin
from .models import Advertisement, HelpTicket, Notification, Setting, Support

admin.site.register(HelpTicket)
admin.site.register(Advertisement)
admin.site.register(Setting)
admin.site.register(Notification)
