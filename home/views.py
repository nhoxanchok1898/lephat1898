from urllib.parse import urlencode

from django.shortcuts import render
from django.urls import reverse
from django.utils import timezone
from datetime import timedelta

# Import store models if available
try:
    from store.models import Brand, Product, Category
except Exception:
    Brand = None
    Product = None
    Category = None


def _product_queryset():
    if Product is None:
        return []
    return Product.with_effective_stock(
        Product.objects.filter(is_active=True).select_related('brand', 'category')
    )


def _catalog_query_url(query):
    base_url = reverse('store:product_list')
    if not query:
        return base_url
    return f"{base_url}?{urlencode({'q': query})}"


def _featured_products(limit):
    queryset = _product_queryset()
    if Product is None:
        return queryset
    return queryset.order_by('-view_count', '-created_at')[:limit]


def home_view(request):
    # Provide simple context using store models if present
    brands = Brand.objects.all()[:8] if Brand is not None else []
    product_queryset = _product_queryset()
    new_products = product_queryset.order_by('-created_at')[:12] if Product is not None else []
    trending_products = product_queryset.order_by('-view_count', '-created_at')[:8] if Product is not None else []
    featured_products = trending_products

    return render(request, 'home/index.html', {
        'brands': brands,
        'new_products': new_products,
        'trending_products': trending_products,
        'featured_products': featured_products,
    })


def blog_hub_view(request):
    articles = [
        {
            'tag': 'Noi that',
            'title': 'Chon son noi that de lau chui ma van giu mat son dep',
            'summary': 'Tong hop cach chon do bong, kha nang che phu va he lot de giam lem ban trong khu vuc sinh hoat hang ngay.',
            'url': _catalog_query_url('Son noi that'),
            'cta': 'Xem vat tu noi that',
        },
        {
            'tag': 'Ngoai that',
            'title': 'Mat tien nang mua can he son nao de ben mau lau hon',
            'summary': 'Goi y cach ket hop son lot, son phu va chong bam bui cho tuong ngoai troi bi nang gat va mua tat.',
            'url': _catalog_query_url('Son ngoai that'),
            'cta': 'Xem son ngoai that',
        },
        {
            'tag': 'Chong tham',
            'title': 'Checklist xu ly san thuong truoc khi len he chong tham',
            'summary': 'Mo ta nhanh cac buoc kiem tra vet nut, do am be mat va cach chon vat tu phu hop cho khu vuc thuong xuyen dong nuoc.',
            'url': _catalog_query_url('Chong tham'),
            'cta': 'Xem giai phap chong tham',
        },
        {
            'tag': 'Vat tu',
            'title': 'Du tru vat tu cho cong trinh nho ma khong bi thieu hang',
            'summary': 'Huong dan lap danh sach vat tu cot loi theo tung hang muc de kiem soat chi phi va tien do giao hang.',
            'url': reverse('store:contact'),
            'cta': 'Nhan tu van du tru',
        },
        {
            'tag': 'Thuong hieu',
            'title': 'Nen bat dau voi Dulux, Jotun, Kova hay Nippon Paint',
            'summary': 'So sanh nhanh theo nhu cau pho bien: nha o, cong trinh can tien do nhanh, va cac be mat can do ben ngoai troi cao.',
            'url': reverse('store:product_list'),
            'cta': 'Xem danh muc thuong hieu',
        },
        {
            'tag': 'Thi cong',
            'title': 'Sai lam thuong gap khi bo qua lop lot va bot ba',
            'summary': 'Ly giai vi sao xu ly nen be mat dung cach giup tiet kiem son phu va giam loi bong troc sau thi cong.',
            'url': _catalog_query_url('Bot ba'),
            'cta': 'Xem vat tu nen be mat',
        },
    ]

    return render(request, 'home/blog_hub.html', {
        'articles': articles,
        'featured_products': _featured_products(4),
    })


def solutions_hub_view(request):
    solutions = [
        {
            'tag': 'Noi that',
            'title': 'He son cho phong khach, phong ngu va khu vuc can lau chui',
            'summary': 'Tap trung vao do phang, kha nang chong bam ban va cam giac hoan thien dep dong deu cho khong gian song.',
            'url': _catalog_query_url('Son noi that'),
            'cta': 'Mo giai phap noi that',
        },
        {
            'tag': 'Ngoai that',
            'title': 'Giai phap mat tien ben mau truoc nang mua va bui do thi',
            'summary': 'Phu hop cho tuong ngoai troi can kha nang chong UV, giam bam bui va duy tri mau sac on dinh theo thoi gian.',
            'url': _catalog_query_url('Son ngoai that'),
            'cta': 'Mo giai phap ngoai that',
        },
        {
            'tag': 'Chong tham',
            'title': 'Khoa am cho san thuong, ban cong, tuong ngoai va khu vuc am',
            'summary': 'De xuat he vat tu theo muc do tham nuoc, vet nut hien huu va nhiet do thi cong thuc te.',
            'url': _catalog_query_url('Chong tham'),
            'cta': 'Mo giai phap chong tham',
        },
        {
            'tag': 'Epoxy',
            'title': 'Son san cho gara, kho nho va khu vuc can ve sinh thuong xuyen',
            'summary': 'Huong tiep can cho be mat can do chiu mai mon, de lau don va giu tinh tham my o khu vuc tai trong vua.',
            'url': _catalog_query_url('Epoxy'),
            'cta': 'Mo giai phap epoxy',
        },
        {
            'tag': 'Kim loai',
            'title': 'He son chong ri cho cong sat, khung thep va hang muc ngoai troi',
            'summary': 'Uu tien xu ly ri set, tang do bam dinh va duy tri lop bao ve cho cac be mat kim loai de xuat.',
            'url': _catalog_query_url('Kim loai'),
            'cta': 'Mo giai phap kim loai',
        },
        {
            'tag': 'Keo va ron',
            'title': 'Chon keo dan gach va cha ron dung khu vuc thi cong',
            'summary': 'Goi y vat tu cho nha tam, bep, ban cong va cac khu vuc can do ben ket dinh va kha nang chong tham tot.',
            'url': _catalog_query_url('Keo'),
            'cta': 'Mo giai phap keo va ron',
        },
    ]

    return render(request, 'home/solutions_hub.html', {
        'solutions': solutions,
        'featured_products': _featured_products(6),
    })
