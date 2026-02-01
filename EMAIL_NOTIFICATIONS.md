# Email Notifications Configuration

## Email Configuration

To set up email notifications, you'll need to configure your email server settings in the application's configuration file. Here’s a typical setup:

```python
EMAIL_BACKEND = 'django.core.mail.backends.smtp.EmailBackend'
EMAIL_HOST = 'smtp.your-email-provider.com'
EMAIL_PORT = 587
EMAIL_USE_TLS = True
EMAIL_HOST_USER = 'your-email@example.com'
EMAIL_HOST_PASSWORD = 'your-email-password'
DEFAULT_FROM_EMAIL = 'your-email@example.com'
```

Make sure to replace placeholders with your actual email server details.

## Celery Setup

Celery is needed for handling asynchronous tasks, including sending email notifications. Follow these steps to set up Celery with your Django application:

1. **Install Celery**  
   Use pip to install Celery:  
   ```bash  
   pip install celery
   ```  

2. **Configure Celery**  
   Create a `celery.py` file in your project directory (where `settings.py` is located) and add the following configuration:
   
   ```python
   from celery import Celery
   import os
   
   os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'your_project.settings')
   app = Celery('your_project')
   app.config_from_object('django.conf:settings', namespace='CELERY')
   app.autodiscover_tasks()
   ```

3. **Create a Task for Sending Emails**  
   In one of your app directories, create a file named `tasks.py` and add the following:
   
   ```python
   from celery import shared_task
   from django.core.mail import send_mail
   
   @shared_task
   def send_email_task(subject, message, recipient_list):
       send_mail(subject, message, 'your-email@example.com', recipient_list)
   ```

4. **Run Celery Worker**  
   Start a Celery worker process with the command:
   ```bash
   celery -A your_project worker --loglevel=info
   ```
   Replace `your_project` with the name of your Django project.

### Final Notes

Make sure to test your email notifications by creating a few test cases!