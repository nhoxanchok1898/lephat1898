from django.conf import settings
from django.core.mail import send_mail
from django.db.models.signals import pre_save, post_save
from django.dispatch import receiver

from .models import Order


def _get_recipient(order: Order):
    # Prefer linked user's email; otherwise skip if not available.
    if order.user and getattr(order.user, "email", None):
        return order.user.email
    return None


def _send_order_email(subject, body, recipient):
    if not recipient:
        return
    from_email = getattr(settings, "DEFAULT_FROM_EMAIL", None) or None
    try:
        send_mail(subject, body, from_email, [recipient], fail_silently=True)
    except Exception:
        # Never raise from signals to avoid breaking save flows
        pass


@receiver(pre_save, sender=Order)
def order_pre_save_track_status(sender, instance: Order, **kwargs):
    if not instance.pk:
        instance._old_status = None  # type: ignore[attr-defined]
        instance._old_is_paid = None  # type: ignore[attr-defined]
    else:
        try:
            old = Order.objects.get(pk=instance.pk)
            instance._old_status = old.status  # type: ignore[attr-defined]
            instance._old_is_paid = old.is_paid  # type: ignore[attr-defined]
        except Order.DoesNotExist:
            instance._old_status = None  # type: ignore[attr-defined]
            instance._old_is_paid = None  # type: ignore[attr-defined]


@receiver(post_save, sender=Order)
def order_post_save_notify(sender, instance: Order, created, **kwargs):
    recipient = _get_recipient(instance)
    if created:
        subject = f"Đơn hàng #{instance.id} đã được tạo"
        body = (
            f"Xin chào {instance.full_name},\n\n"
            f"Đơn hàng #{instance.id} của bạn đã được tạo và đang chờ xử lý.\n"
            f"Trạng thái hiện tại: {instance.get_status_display()}.\n\n"
            "Cảm ơn bạn đã mua hàng!"
        )
        _send_order_email(subject, body, recipient)
        return

    # Status change notification
    old_status = getattr(instance, "_old_status", None)
    if old_status and old_status != instance.status:
        subject = f"Đơn hàng #{instance.id} cập nhật trạng thái"
        body = (
            f"Xin chào {instance.full_name},\n\n"
            f"Đơn hàng #{instance.id} đã chuyển từ '{dict(Order.STATUS_CHOICES).get(old_status, old_status)}' "
            f"sang '{instance.get_status_display()}'.\n\n"
            "Cảm ơn bạn đã mua hàng!"
        )
        _send_order_email(subject, body, recipient)

    # Paid notification (trigger only on transition False -> True)
    old_is_paid = getattr(instance, "_old_is_paid", None)
    if old_is_paid is False and instance.is_paid:
        subject = f"Đơn hàng #{instance.id} đã được thanh toán"
        body = (
            f"Xin chào {instance.full_name},\n\n"
            f"Đơn hàng #{instance.id} đã được xác nhận thanh toán thành công.\n"
            f"Phương thức: {instance.get_payment_method_display()}.\n"
            f"Trạng thái hiện tại: {instance.get_status_display()}.\n\n"
            "Cảm ơn bạn đã mua hàng!"
        )
        _send_order_email(subject, body, recipient)
