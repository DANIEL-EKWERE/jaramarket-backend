from rest_framework import serializers
from .models import Bank, TransactionLog, Transfer, Wallet


class WalletSerializer(serializers.ModelSerializer):
    class Meta:
        model = Wallet
        fields = ["id", "balance", "created_at"]


class BankSerializer(serializers.ModelSerializer):
    class Meta:
        model = Bank
        fields = ["id", "name", "code", "slug"]


class TransactionSerializer(serializers.ModelSerializer):
    amount_major = serializers.ReadOnlyField()

    class Meta:
        model = TransactionLog
        fields = [
            "id", "reference", "transaction_type", "amount", "amount_major",
            "old_balance", "new_balance", "currency", "comment",
            "is_refund", "has_refund", "status", "created_at",
        ]


class TransferSerializer(serializers.ModelSerializer):
    class Meta:
        model = Transfer
        fields = [
            "id", "reference", "amount", "bank_name", "account_number",
            "account_name", "status", "failures", "created_at",
        ]
