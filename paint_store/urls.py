from django.contrib import admin
from django.urls import path, include
from django.conf import settings
from django.conf.urls.static import static
from django.views.generic import TemplateView
from django.contrib.auth import views as auth_views
from django.contrib.sitemaps.views import sitemap

from store.sitemaps import ProductSitemap, StaticViewSitemap

sitemaps = {
    'products': ProductSitemap(),
    'static': StaticViewSitemap(),
}

urlpatterns = [
    path('admin/', admin.site.urls),
    path('api/', include('store.api_urls')),
    # Auth URLs are handled in store/urls.py to avoid duplication
    path('', include('store.urls', namespace='store')),
    path('sitemap.xml', sitemap, {'sitemaps': sitemaps}, name='sitemap'),
    path('robots.txt', TemplateView.as_view(template_name='robots.txt', content_type='text/plain')),
]

if settings.DEBUG:
    urlpatterns += static(settings.MEDIA_URL, document_root=settings.MEDIA_ROOT)