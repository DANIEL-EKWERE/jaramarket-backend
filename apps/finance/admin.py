from django.contrib import admin
from .models import Bank, BankAccount, Commission, PaymentLog, PaymentNow, Transfer, TransactionLog, Wallet

admin.site.register(Wallet)
admin.site.register(Bank)
admin.site.register(Commission)
admin.site.register(TransactionLog)
admin.site.register(Transfer)
