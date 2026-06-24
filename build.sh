# #!/usr/bin/env bash
# # exist on error

# set -o errexit

# pip install -r requirements.txt

# pip install gunicorn

# python manage.py collectstatic --no-input

# # python manage.py migrate

# python manage.py makemigrations users stories chapters coins comments tips audio branching notifications

# python3 manage.py seed_data

#!/usr/bin/env bash
# exit on error
set -o errexit

pip install -r requirements.txt
pip install gunicorn

python manage.py collectstatic --no-input

# 1. Actually apply the migrations to the database
# python manage.py migrate --no-input # temporary fix

#python manage.py migrate chapters

# 2. Run your seed script
#python manage.py seed_data

# # Create the fix_word_counts management command
# mkdir -p apps/chapters/management/commands
# touch apps/chapters/management/__init__.py
# touch apps/chapters/management/commands/__init__.py

# cat > apps/chapters/management/commands/fix_word_counts.py << 'EOF'
# from django.core.management.base import BaseCommand
# from apps.chapters.models import Chapter, count_words

# class Command(BaseCommand):
#     help = 'Recompute word counts stripping HTML tags'

#     def handle(self, *args, **kwargs):
#         chapters = Chapter.objects.all()
#         updated = 0
#         for ch in chapters:
#             correct_count = count_words(ch.content)
#             if ch.word_count != correct_count:
#                 ch.word_count = correct_count
#                 ch.save(update_fields=['word_count'])
#                 updated += 1
#         self.stdout.write(f'Fixed {updated} out of {chapters.count()} chapters')
# EOF

# python manage.py fix_word_counts