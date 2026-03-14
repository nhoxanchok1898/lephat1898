from django.conf import settings


def business_info(request):
    return {
        'business_name': settings.BUSINESS_NAME,
        'business_phone_display': settings.BUSINESS_PHONE_DISPLAY,
        'business_phone_href': settings.BUSINESS_PHONE_HREF,
        'business_email': settings.BUSINESS_EMAIL,
        'business_email_href': settings.BUSINESS_EMAIL_HREF,
        'business_address': settings.BUSINESS_ADDRESS,
        'business_hours': settings.BUSINESS_HOURS,
        'business_service_areas': settings.BUSINESS_SERVICE_AREAS,
        'business_zalo_url': settings.BUSINESS_ZALO_URL,
        'business_maps_url': settings.BUSINESS_MAPS_URL,
    }
