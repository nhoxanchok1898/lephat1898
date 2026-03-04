<?php
/**
 * Seed WooCommerce catalog with additional multi-brand products.
 *
 * Run:
 *   Get-Content -Raw tools/import_multibrand_seed_catalog.php | docker exec -i lephat1898-wordpress-1 php
 */

require '/var/www/html/wp-load.php';

if (!function_exists('wc_get_product')) {
    fwrite(STDERR, "WooCommerce is not loaded.\n");
    exit(1);
}

if (!function_exists('wp_generate_attachment_metadata')) {
    require_once ABSPATH . 'wp-admin/includes/image.php';
}
if (!function_exists('wp_insert_attachment')) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
}
if (!function_exists('wp_upload_bits')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}

$taxonomy_brand = '';
foreach (['pa_brand', 'product_brand', 'brand'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_brand = $tax;
        break;
    }
}

$taxonomy_line = '';
foreach (['pa_line', 'product_line', 'line'] as $tax) {
    if (taxonomy_exists($tax)) {
        $taxonomy_line = $tax;
        break;
    }
}

$brand_labels = [
    'jotun' => 'Jotun',
    'nippon' => 'Nippon',
    'kova' => 'Kova',
    'toa' => 'TOA',
    'sika' => 'Sika',
    'apollo' => 'Apollo',
];

$image_base = '/var/www/html/wp-content/themes/my-theme/assets/dulux_import/';
$brand_image_pools = [
    'jotun' => [
        'image011.jpg', 'image012.jpg', 'image013.jpg', 'image014.jpg', 'image015.jpg',
        'image016.jpg', 'image017.jpg', 'image018.jpg', 'image019.jpg', 'image020.jpg',
    ],
    'nippon' => [
        'image021.jpg', 'image022.jpg', 'image023.jpg', 'image024.jpg', 'image030.jpg',
        'image031.jpg', 'image032.jpg', 'image048.jpg', 'image049.jpg', 'image050.jpg',
    ],
    'kova' => [
        'image051.jpg', 'image052.jpg', 'image053.jpg', 'image054.jpg', 'image055.jpg',
        'image056.jpg', 'image058.jpg', 'image060.jpg', 'image061.jpg', 'image062.jpg',
    ],
    'toa' => [
        'image060.jpg', 'image061.jpg', 'image062.jpg', 'image065.jpg', 'image069.jpg',
        'image070.jpg', 'image071.jpg', 'image072.jpg', 'image081.jpg', 'image058.jpg',
    ],
    'sika' => [
        'image065.jpg', 'image069.jpg', 'image070.jpg', 'image071.jpg', 'image072.jpg',
        'image081.jpg', 'image056.jpg', 'image058.jpg', 'image060.jpg', 'image061.jpg',
    ],
    'apollo' => [
        'a300.jpg', 'a300-1.jpg', 'a500.jpeg', 'a500-1.jpeg', 'a600.jpeg',
        'a600-1.jpeg', 'a800.jpeg', 'a800-1.jpeg', 'a1000.jpeg', 'a1000-1.jpeg',
    ],
];

$items = [
    // Jotun
    [
        'slug' => 'jotun-majestic-primer-5l',
        'name' => 'Son lot Jotun Majestic Primer noi that',
        'brand' => 'jotun',
        'category' => 'son-lot',
        'packs' => [['1L', 220000], ['5L', 950000], ['18L', 3240000]],
        'stock' => 24,
        'desc' => 'Son lot noi that Majestic Primer, tang do bam dinh va be mat min.',
    ],
    [
        'slug' => 'jotashield-ben-mau-5l',
        'name' => 'Son ngoai that Jotun Jotashield ben mau',
        'brand' => 'jotun',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 290000], ['5L', 1290000], ['17L', 4190000]],
        'stock' => 20,
        'desc' => 'Son ngoai that Jotashield, chong phai mau va chong bam ban.',
    ],
    [
        'slug' => 'jotun-waterguard-4l',
        'name' => 'Son chong tham Jotun WaterGuard',
        'brand' => 'jotun',
        'category' => 'chong-tham',
        'packs' => [['4L', 780000], ['20L', 3490000]],
        'stock' => 18,
        'desc' => 'Giai phap chong tham he nuoc cho tuong dung va san thuong.',
    ],
    [
        'slug' => 'jotun-majestic-silk-5l',
        'name' => 'Son noi that Jotun Majestic Silk',
        'brand' => 'jotun',
        'category' => 'son-noi-that',
        'packs' => [['1L', 260000], ['5L', 1140000], ['15L', 3320000]],
        'stock' => 22,
        'desc' => 'Majestic Silk cho noi that, do min dep va mau sac sang.',
    ],
    [
        'slug' => 'jotun-essence-de-lau-chui-5l',
        'name' => 'Son noi that Jotun Essence de lau chui',
        'brand' => 'jotun',
        'category' => 'son-noi-that',
        'packs' => [['1L', 190000], ['5L', 790000], ['18L', 2490000]],
        'stock' => 28,
        'desc' => 'Dong son Essence cho nha dan dung, kinh te va de ve sinh.',
    ],
    [
        'slug' => 'jotun-jotaplast-noi-that-18l',
        'name' => 'Son noi that Jotun Jotaplast',
        'brand' => 'jotun',
        'category' => 'son-noi-that',
        'packs' => [['5L', 650000], ['18L', 2190000]],
        'stock' => 26,
        'desc' => 'Jotaplast noi that phu hop cong trinh can toi uu chi phi.',
    ],
    [
        'slug' => 'jotashield-clean-extreme-5l',
        'name' => 'Son ngoai that Jotun Jotashield Clean',
        'brand' => 'jotun',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 315000], ['5L', 1370000], ['17L', 4450000]],
        'stock' => 15,
        'desc' => 'Jotashield Clean giam bam ban, bao ve mat tien ben dep.',
    ],
    [
        'slug' => 'jotun-gardex-metal-primer-0-8l',
        'name' => 'Son kim loai Jotun Gardex Metal Primer',
        'brand' => 'jotun',
        'category' => 'son-kim-loai',
        'packs' => [['0.8L', 178000], ['2.5L', 535000], ['17.5L', 3360000]],
        'stock' => 20,
        'desc' => 'Son lot chong ri cho sat thep, tang do ben cho lop phu.',
    ],
    [
        'slug' => 'jotun-penguard-primer-5l',
        'name' => 'Son epoxy Jotun Penguard Primer',
        'brand' => 'jotun',
        'category' => 'son-epoxy',
        'packs' => [['5L', 1290000], ['20L', 4680000]],
        'stock' => 12,
        'desc' => 'Penguard Primer he epoxy 2 thanh phan cho cong nghiep.',
    ],
    [
        'slug' => 'jotun-jotamastic-87-20l',
        'name' => 'Son cong nghiep Jotun Jotamastic 87',
        'brand' => 'jotun',
        'category' => 'son-cong-nghiep',
        'packs' => [['5L', 1580000], ['20L', 5960000]],
        'stock' => 10,
        'desc' => 'Jotamastic 87 do ben cao cho ket cau thep va moi truong khac nghiet.',
    ],

    // Nippon
    [
        'slug' => 'nippon-odourless-5l',
        'name' => 'Son noi that Nippon Odour-less',
        'brand' => 'nippon',
        'category' => 'son-noi-that',
        'packs' => [['1L', 215000], ['5L', 890000], ['18L', 2990000]],
        'stock' => 30,
        'desc' => 'Odour-less noi that mui nhe, de thi cong trong khong gian kin.',
    ],
    [
        'slug' => 'nippon-weatherbond-5l',
        'name' => 'Son ngoai that Nippon WeatherGard Plus',
        'brand' => 'nippon',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 275000], ['5L', 1180000], ['18L', 3920000]],
        'stock' => 21,
        'desc' => 'Dong son ngoai that ben mau, chong tia UV va thoi tiet khac nghiet.',
    ],
    [
        'slug' => 'nippon-skim-coat-40kg',
        'name' => 'Bot tret Nippon Skim Coat',
        'brand' => 'nippon',
        'category' => 'bot-tret',
        'packs' => [['40kg', 240000]],
        'stock' => 50,
        'desc' => 'Skim Coat giup lam phang be mat va tang do bam dinh cho son.',
    ],
    [
        'slug' => 'nippon-vinilex-5000-5l',
        'name' => 'Son lot Nippon Vinilex 130 Active Primer',
        'brand' => 'nippon',
        'category' => 'son-lot',
        'packs' => [['1L', 185000], ['5L', 760000], ['18L', 2420000]],
        'stock' => 27,
        'desc' => 'Son lot goc nuoc giup tang do bam dinh cho lop son phu noi ngoai that.',
    ],
    [
        'slug' => 'nippon-super-matex-5l',
        'name' => 'Son noi that Nippon Super Matex',
        'brand' => 'nippon',
        'category' => 'son-noi-that',
        'packs' => [['1L', 175000], ['5L', 690000], ['18L', 2150000]],
        'stock' => 32,
        'desc' => 'Super Matex be mat min, de su dung cho nha dan dung.',
    ],
    [
        'slug' => 'nippon-matex-sieu-trang-5l',
        'name' => 'Son noi that Nippon Matex sieu trang',
        'brand' => 'nippon',
        'category' => 'son-noi-that',
        'packs' => [['1L', 168000], ['5L', 650000], ['18L', 2080000]],
        'stock' => 34,
        'desc' => 'Matex tong mau trang, thich hop son tran va tuong noi that.',
    ],
    [
        'slug' => 'nippon-odourless-easywash-5l',
        'name' => 'Son noi that Nippon Odour-less EasyWash',
        'brand' => 'nippon',
        'category' => 'son-noi-that',
        'packs' => [['1L', 238000], ['5L', 980000], ['18L', 3220000]],
        'stock' => 19,
        'desc' => 'Odour-less EasyWash de lau chui, phu hop nha co tre nho.',
    ],
    [
        'slug' => 'nippon-hydroshield-5l',
        'name' => 'Son chong tham Nippon WP-100',
        'brand' => 'nippon',
        'category' => 'chong-tham',
        'packs' => [['5L', 880000], ['20L', 3260000]],
        'stock' => 16,
        'desc' => 'Giai phap chong tham goc xi mang cho tuong dung, san mai va khu vuc am uot.',
    ],
    [
        'slug' => 'nippon-exterior-sealer-5l',
        'name' => 'Son lot Nippon Odour-less Sealer',
        'brand' => 'nippon',
        'category' => 'son-lot',
        'packs' => [['1L', 195000], ['5L', 790000], ['18L', 2590000]],
        'stock' => 22,
        'desc' => 'Son lot noi that mui nhe, tang do bam va do phang cho be mat truoc khi son phu.',
    ],
    [
        'slug' => 'nippon-bodelac-9000-0-9l',
        'name' => 'Son dau Nippon Bodelac 9000',
        'brand' => 'nippon',
        'category' => 'son-dau',
        'packs' => [['0.9L', 186000], ['3L', 598000], ['18L', 3340000]],
        'stock' => 14,
        'desc' => 'Bodelac 9000 son dau cho go va kim loai, do bong dep.',
    ],

    // Kova
    [
        'slug' => 'kova-k209-20kg',
        'name' => 'Son lot ngoai that Kova K-209',
        'brand' => 'kova',
        'category' => 'son-lot',
        'packs' => [['5kg', 220000], ['20kg', 560000]],
        'stock' => 36,
        'desc' => 'Son lot khang kiem goc nuoc cho he son ngoai that, tang bam dinh va do ben.',
    ],
    [
        'slug' => 'kova-ct11a-plus-5l',
        'name' => 'Son chong tham Kova CT-11A Plus',
        'brand' => 'kova',
        'category' => 'chong-tham',
        'packs' => [['5kg', 260000], ['20kg', 990000]],
        'stock' => 28,
        'desc' => 'CT-11A Plus chuyen dung cho ngoai that va vi tri tiep xuc nuoc.',
    ],
    [
        'slug' => 'kova-bot-tret-noi-that-40kg',
        'name' => 'Bot tret Kova noi that',
        'brand' => 'kova',
        'category' => 'bot-tret',
        'packs' => [['40kg', 210000]],
        'stock' => 48,
        'desc' => 'Bot tret noi that Kova lam min be mat truoc khi son phu.',
    ],
    [
        'slug' => 'kova-ct11a-san-thuong-20kg',
        'name' => 'Son chong tham Kova CT-11A san thuong',
        'brand' => 'kova',
        'category' => 'chong-tham',
        'packs' => [['5kg', 248000], ['20kg', 950000]],
        'stock' => 24,
        'desc' => 'CT-11A cho san thuong, mai va khu vuc tiep xuc mua nang.',
    ],
    [
        'slug' => 'kova-k261-son-lot-khang-kiem-20kg',
        'name' => 'Son ngoai that Kova K-261 Plus',
        'brand' => 'kova',
        'category' => 'son-ngoai-that',
        'packs' => [['4kg', 205000], ['20kg', 840000]],
        'stock' => 23,
        'desc' => 'Son phu ngoai that ben mau, han che bam ban va phu hop dieu kien khi hau nong am.',
    ],
    [
        'slug' => 'kova-k871-ngoai-that-5l',
        'name' => 'Son noi that cao cap Kova K-871',
        'brand' => 'kova',
        'category' => 'son-noi-that',
        'packs' => [['1L', 195000], ['5L', 860000], ['20L', 2980000]],
        'stock' => 20,
        'desc' => 'Son noi that cao cap be mat min, do phu cao va de thi cong.',
    ],
    [
        'slug' => 'kova-k5501-noi-that-5l',
        'name' => 'Son ngoai that cao cap Kova K-5501 Plus',
        'brand' => 'kova',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 175000], ['5L', 690000], ['18L', 2260000]],
        'stock' => 29,
        'desc' => 'Son ngoai that cao cap, ben mau va chong bam ban cho mat dung cong trinh.',
    ],
    [
        'slug' => 'kova-matit-deo-ngoai-that-40kg',
        'name' => 'Bot tret Kova matit deo ngoai that',
        'brand' => 'kova',
        'category' => 'bot-tret',
        'packs' => [['40kg', 232000]],
        'stock' => 34,
        'desc' => 'Matit deo ngoai that, han che nut va tang do ben cho lop son.',
    ],
    [
        'slug' => 'kova-son-kim-loai-metal-primer-0-8l',
        'name' => 'Son kim loai Kova Metal Primer',
        'brand' => 'kova',
        'category' => 'son-kim-loai',
        'packs' => [['0.8L', 165000], ['3L', 548000], ['18L', 2860000]],
        'stock' => 17,
        'desc' => 'Son lot kim loai cho sat thep, chong ri set va bam dinh cao.',
    ],
    [
        'slug' => 'kova-son-epoxy-san-cong-nghiep-20kg',
        'name' => 'Son epoxy Kova san cong nghiep',
        'brand' => 'kova',
        'category' => 'son-epoxy',
        'packs' => [['5kg', 980000], ['20kg', 3580000]],
        'stock' => 11,
        'desc' => 'He son epoxy cho san xuong, tang do cung va kha nang chiu mai mon.',
    ],

    // TOA
    [
        'slug' => 'toa-supershield-noi-that-de-lau-chui-5l',
        'name' => 'Son noi that TOA SuperShield de lau chui',
        'brand' => 'toa',
        'category' => 'son-noi-that',
        'packs' => [['1L', 198000], ['5L', 820000], ['18L', 2680000]],
        'stock' => 27,
        'desc' => 'SuperShield noi that de lau chui, mau dep va de thi cong.',
    ],
    [
        'slug' => 'toa-supershield-ngoai-that-ben-mau-5l',
        'name' => 'Son ngoai that TOA SuperShield ben mau',
        'brand' => 'toa',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 278000], ['5L', 1190000], ['18L', 3920000]],
        'stock' => 20,
        'desc' => 'SuperShield ngoai that chong bam ban va ben mau truoc thoi tiet.',
    ],
    [
        'slug' => 'toa-4seasons-ngoai-that-5l',
        'name' => 'Son ngoai that TOA 4Seasons',
        'brand' => 'toa',
        'category' => 'son-ngoai-that',
        'packs' => [['1L', 235000], ['5L', 980000], ['18L', 3180000]],
        'stock' => 22,
        'desc' => '4Seasons dong son ngoai that kinh te, phu hop nha pho.',
    ],
    [
        'slug' => 'toa-nanoshield-noi-that-5l',
        'name' => 'Son noi that TOA NanoShield',
        'brand' => 'toa',
        'category' => 'son-noi-that',
        'packs' => [['1L', 216000], ['5L', 890000], ['18L', 2890000]],
        'stock' => 24,
        'desc' => 'NanoShield noi that voi do phu tot va mau sac on dinh.',
    ],
    [
        'slug' => 'toa-1000-lot-khang-kiem-5l',
        'name' => 'Son lot TOA 1000 khang kiem',
        'brand' => 'toa',
        'category' => 'son-lot',
        'packs' => [['1L', 172000], ['5L', 690000], ['18L', 2240000]],
        'stock' => 26,
        'desc' => 'TOA 1000 son lot khang kiem tang do bam cho son phu.',
    ],
    [
        'slug' => 'toa-waterproof-201-20kg',
        'name' => 'Son chong tham TOA Waterproof 201',
        'brand' => 'toa',
        'category' => 'chong-tham',
        'packs' => [['5kg', 255000], ['20kg', 940000]],
        'stock' => 21,
        'desc' => 'Waterproof 201 cho khu vuc tiep xuc mua nang va do am cao.',
    ],
    [
        'slug' => 'toa-bot-tret-noi-that-40kg',
        'name' => 'Bot tret TOA noi that',
        'brand' => 'toa',
        'category' => 'bot-tret',
        'packs' => [['40kg', 218000]],
        'stock' => 33,
        'desc' => 'Bot tret TOA noi that cho be mat min va de son phu.',
    ],
    [
        'slug' => 'toa-rust-tech-kim-loai-primer-0-8l',
        'name' => 'Son kim loai TOA Rust Tech Primer',
        'brand' => 'toa',
        'category' => 'son-kim-loai',
        'packs' => [['0.8L', 158000], ['2.5L', 498000], ['18L', 2790000]],
        'stock' => 19,
        'desc' => 'Rust Tech primer cho sat thep, han che ri set va bong troc.',
    ],
    [
        'slug' => 'toa-epoxy-floor-topcoat-20kg',
        'name' => 'Son epoxy TOA Floor Topcoat',
        'brand' => 'toa',
        'category' => 'son-epoxy',
        'packs' => [['5kg', 920000], ['20kg', 3440000]],
        'stock' => 13,
        'desc' => 'He epoxy san cong nghiep, chiu mai mon va de ve sinh.',
    ],
    [
        'slug' => 'toa-cong-nghiep-weatherproof-18l',
        'name' => 'Son cong nghiep TOA Weatherproof',
        'brand' => 'toa',
        'category' => 'son-cong-nghiep',
        'packs' => [['5L', 1120000], ['18L', 3890000]],
        'stock' => 12,
        'desc' => 'Dong son cong nghiep TOA cho be mat can do ben ngoai troi.',
    ],

    // Sika
    [
        'slug' => 'sikalatex-th-5l',
        'name' => 'Phu gia SikaLatex TH',
        'brand' => 'sika',
        'category' => 'keo-va-phu-gia',
        'packs' => [['1L', 165000], ['5L', 760000], ['20L', 2840000]],
        'stock' => 25,
        'desc' => 'SikaLatex TH phu gia tang bam dinh cho vua va lop chong tham.',
    ],
    [
        'slug' => 'sikatop-seal-107-25kg',
        'name' => 'Chong tham SikaTop Seal-107',
        'brand' => 'sika',
        'category' => 'chong-tham',
        'packs' => [['5kg', 265000], ['25kg', 1220000]],
        'stock' => 20,
        'desc' => 'SikaTop Seal-107 he chong tham hai thanh phan cho be tong.',
    ],
    [
        'slug' => 'sikaguard-905w-5l',
        'name' => 'Son lot SikaGuard 905W',
        'brand' => 'sika',
        'category' => 'son-lot',
        'packs' => [['1L', 230000], ['5L', 980000], ['20L', 3490000]],
        'stock' => 16,
        'desc' => 'SikaGuard 905W tang bao ve be mat truoc moi truong khac nghiet.',
    ],
    [
        'slug' => 'sikafloor-263-20kg',
        'name' => 'Son epoxy SikaFloor 263',
        'brand' => 'sika',
        'category' => 'son-epoxy',
        'packs' => [['5kg', 1280000], ['20kg', 4860000]],
        'stock' => 11,
        'desc' => 'SikaFloor 263 cho san epoxy cong nghiep, do ben co hoc cao.',
    ],
    [
        'slug' => 'sikafloor-81-epocem-25kg',
        'name' => 'Son cong nghiep SikaFloor 81 EpoCem',
        'brand' => 'sika',
        'category' => 'son-cong-nghiep',
        'packs' => [['25kg', 2380000]],
        'stock' => 8,
        'desc' => 'He san cong nghiep chiu am, chiu mai mon tot cho nha xuong.',
    ],
    [
        'slug' => 'sikaceram-200-tilefix-25kg',
        'name' => 'Keo dan gach SikaCeram 200 TileFix',
        'brand' => 'sika',
        'category' => 'keo-va-phu-gia',
        'packs' => [['25kg', 312000]],
        'stock' => 35,
        'desc' => 'SikaCeram 200 keo dan gach cho op lat trong va ngoai nha.',
    ],
    [
        'slug' => 'sikagrout-214-25kg',
        'name' => 'Vua rot SikaGrout 214',
        'brand' => 'sika',
        'category' => 'keo-va-phu-gia',
        'packs' => [['25kg', 428000]],
        'stock' => 28,
        'desc' => 'SikaGrout 214 vua rot khong co ngot cho be tong va may moc.',
    ],
    [
        'slug' => 'sika-monotop-615-25kg',
        'name' => 'Vua sua chua Sika MonoTop 615',
        'brand' => 'sika',
        'category' => 'chong-tham',
        'packs' => [['25kg', 515000]],
        'stock' => 19,
        'desc' => 'Sika MonoTop 615 cho sua chua be tong va tang cuong bao ve.',
    ],
    [
        'slug' => 'sikaflex-construction-600ml',
        'name' => 'Keo tram khe SikaFlex Construction',
        'brand' => 'sika',
        'category' => 'keo-va-phu-gia',
        'packs' => [['600ml/chai', 158000]],
        'stock' => 42,
        'desc' => 'SikaFlex tram khe co gian, bam dinh tot cho nhieu vat lieu.',
    ],
    [
        'slug' => 'sika-primer-3n-1l',
        'name' => 'Son lot Sika Primer 3N',
        'brand' => 'sika',
        'category' => 'son-lot',
        'packs' => [['1L', 365000], ['5L', 1680000]],
        'stock' => 12,
        'desc' => 'Sika Primer 3N tang bam dinh cho he keo va lop phu chuyen dung.',
    ],

    // Apollo
    [
        'slug' => 'apollo-acrylic-sealant-a100',
        'name' => 'Keo acrylic Apollo Sealant A100',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 32000], ['Thung 25 chai', 780000]],
        'stock' => 56,
        'desc' => 'Apollo A100 acrylic sealant cho khe noi that va vet nut nho.',
    ],
    [
        'slug' => 'apollo-silicone-sealant-a200',
        'name' => 'Keo silicone Apollo Sealant A200',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 36000], ['Thung 25 chai', 870000]],
        'stock' => 52,
        'desc' => 'Apollo A200 silicone trung tinh cho nhieu vat lieu thong dung.',
    ],
    [
        'slug' => 'apollo-silicone-sealant-a300',
        'name' => 'Keo silicone Apollo Sealant A300',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 42000], ['Thung 25 chai', 1020000]],
        'stock' => 49,
        'desc' => 'Apollo A300 silicone da dung, bam dinh va dan hoi on dinh.',
    ],
    [
        'slug' => 'apollo-silicone-sealant-a500',
        'name' => 'Keo silicone Apollo Sealant A500',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 56000], ['Thung 25 chai', 1360000]],
        'stock' => 44,
        'desc' => 'Apollo A500 silicone ket cau phu hop kinh va nhom ngoai that.',
    ],
    [
        'slug' => 'apollo-silicone-sealant-a600',
        'name' => 'Keo silicone Apollo Sealant A600',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 65000], ['Thung 25 chai', 1580000]],
        'stock' => 38,
        'desc' => 'Apollo A600 silicone da nang cho cong trinh can do ben thoi tiet.',
    ],
    [
        'slug' => 'apollo-silicone-sealant-sanitary-n',
        'name' => 'Keo silicone Apollo Sealant Sanitary-N',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 72000], ['Thung 25 chai', 1750000]],
        'stock' => 33,
        'desc' => 'Apollo Sanitary-N chuyen dung cho khu vuc am uot, han che moc den.',
    ],
    [
        'slug' => 'apollo-silicone-weatherseal-a68',
        'name' => 'Keo silicone Apollo Weatherseal A68',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['310ml/chai', 95000], ['Thung 25 chai', 2325000], ['500ml/sausage', 145000], ['Thung 20 sausage', 2850000]],
        'stock' => 28,
        'desc' => 'Apollo Weatherseal A68 silicone trung tinh cho mat dung va khe lien ket.',
    ],
    [
        'slug' => 'apollo-silicone-weatherseal-a79',
        'name' => 'Keo silicone Apollo Weatherseal A79',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['300ml/chai', 108000], ['Thung 25 chai', 2625000]],
        'stock' => 24,
        'desc' => 'Apollo Weatherseal A79 dong cao cap cho mat dung can do ben cao.',
    ],
    [
        'slug' => 'apollo-pu-foam',
        'name' => 'Apollo PU Foam',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['750ml/chai', 98000], ['Thung 12 chai', 1140000]],
        'stock' => 31,
        'desc' => 'Apollo PU Foam no dien khe hong, cach nhiet va giam am hieu qua.',
    ],
    [
        'slug' => 'apollo-pu-foam-b1',
        'name' => 'Apollo PU Foam B1',
        'brand' => 'apollo',
        'category' => 'keo-va-phu-gia',
        'packs' => [['750ml/chai', 128000], ['Thung 12 chai', 1500000]],
        'stock' => 19,
        'desc' => 'Apollo PU Foam B1 giam bat lua, phu hop vi tri yeu cau an toan chay.',
    ],
];

$normalize_vi_name = static function (string $name): string {
    $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    if ($name === '') {
        return '';
    }

    $replacements = [
        '/\bSon\b/u' => 'Sơn',
        '/\bson\b/u' => 'sơn',
        '/\bBot\b/u' => 'Bột',
        '/\bbot\b/u' => 'bột',
        '/\bVua\b/u' => 'Vữa',
        '/\bvua\b/u' => 'vữa',
        '/\bChong\b/u' => 'Chống',
        '/\bchong\b/u' => 'chống',
        '/\blot\b/u' => 'lót',
        '/\bngoai\b/u' => 'ngoại',
        '/\bthat\b/u' => 'thất',
        '/\bnoi\b/u' => 'nội',
        '/\btham\b/u' => 'thấm',
        '/\bde\b/u' => 'dễ',
        '/\bchui\b/u' => 'chùi',
        '/\bkim\b/u' => 'kim',
        '/\bloai\b/u' => 'loại',
        '/\bcong\b/u' => 'công',
        '/\bnghiep\b/u' => 'nghiệp',
        '/\btret\b/u' => 'trét',
        '/\bmatit\b/u' => 'ma-tít',
        '/\bdeo\b/u' => 'dẻo',
        '/\bdau\b/u' => 'dầu',
        '/\bsieu\b/u' => 'siêu',
        '/\btrang\b/u' => 'trắng',
        '/\bkhang\b/u' => 'kháng',
        '/\bkiem\b/u' => 'kiềm',
        '/\bdan\b/u' => 'dán',
        '/\bgach\b/u' => 'gạch',
        '/\btram\b/u' => 'trám',
        '/\brot\b/u' => 'rót',
        '/\bsua\b/u' => 'sửa',
        '/\bchua\b/u' => 'chữa',
        '/\bsan\b/u' => 'sàn',
        '/\bthuong\b/u' => 'thượng',
        '/\bben\b/u' => 'bền',
        '/\bmau\b/u' => 'màu',
    ];

    return (string) preg_replace(array_keys($replacements), array_values($replacements), $name);
};

$ensure_term = function ($term_name, $taxonomy, $slug = '') {
    $slug = sanitize_title((string) $slug);
    if ($slug !== '') {
        $existing = get_term_by('slug', $slug, $taxonomy);
        if ($existing instanceof WP_Term) {
            return (int) $existing->term_id;
        }
    }

    $exists = term_exists($term_name, $taxonomy);
    if ($exists) {
        return is_array($exists) ? (int) $exists['term_id'] : (int) $exists;
    }

    $res = wp_insert_term($term_name, $taxonomy, [
        'slug' => ($slug !== '' ? $slug : sanitize_title($term_name)),
    ]);
    if (is_wp_error($res)) {
        return 0;
    }
    return (int) $res['term_id'];
};

$attachment_for_file = function ($source_file, $cache_hint = '') {
    $source_file = (string) $source_file;
    if ($source_file === '' || !file_exists($source_file)) {
        return 0;
    }

    $cache_key = 'my_theme_seed_img_' . md5($source_file . '|' . $cache_hint);
    $cached_id = (int) get_option($cache_key, 0);
    if ($cached_id > 0 && get_post($cached_id) instanceof WP_Post) {
        return $cached_id;
    }

    $filename = basename($source_file);
    $content = file_get_contents($source_file);
    if ($content === false || $content === '') {
        return 0;
    }

    $bits = wp_upload_bits('seed-' . sanitize_title($cache_hint) . '-' . $filename, null, $content);
    if (!empty($bits['error']) || empty($bits['file'])) {
        return 0;
    }

    $title_hint = trim((string) $cache_hint);
    if ($title_hint === '') {
        $title_hint = pathinfo($filename, PATHINFO_FILENAME);
    }

    $filetype = wp_check_filetype($bits['file']);
    $attach_id = wp_insert_attachment([
        'post_mime_type' => $filetype['type'] ?: 'image/jpeg',
        'post_title' => 'Seed image ' . strtoupper((string) $title_hint),
        'post_content' => '',
        'post_status' => 'inherit',
    ], $bits['file']);

    if ($attach_id <= 0) {
        return 0;
    }

    $meta = wp_generate_attachment_metadata($attach_id, $bits['file']);
    if (is_array($meta)) {
        wp_update_attachment_metadata($attach_id, $meta);
    }

    update_option($cache_key, (int) $attach_id, false);
    return (int) $attach_id;
};

$pick_seed_image_file = function ($brand_slug, $product_slug) use ($brand_image_pools, $image_base) {
    $brand_slug = sanitize_title((string) $brand_slug);
    $product_slug = sanitize_title((string) $product_slug);

    $pool = isset($brand_image_pools[$brand_slug]) && is_array($brand_image_pools[$brand_slug])
        ? $brand_image_pools[$brand_slug]
        : [];
    if (empty($pool)) {
        return '';
    }

    $index = abs((int) crc32($product_slug));
    $index = $index % count($pool);

    for ($i = 0; $i < count($pool); $i++) {
        $candidate = $pool[($index + $i) % count($pool)];
        $full = $image_base . $candidate;
        if (file_exists($full)) {
            return $full;
        }
    }

    return '';
};

$stats = ['created' => 0, 'updated' => 0, 'errors' => 0];

foreach ($items as $item) {
    $slug = sanitize_title((string) ($item['slug'] ?? ''));
    $name_raw = trim((string) ($item['name'] ?? ''));
    $name = $normalize_vi_name($name_raw);
    $brand_slug = sanitize_title((string) ($item['brand'] ?? ''));
    $category_slug = sanitize_title((string) ($item['category'] ?? ''));
    $packs = isset($item['packs']) && is_array($item['packs']) ? $item['packs'] : [];
    $stock_qty = max(0, (int) ($item['stock'] ?? 20));
    $desc = trim((string) ($item['desc'] ?? ''));

    if ($slug === '' || $name === '' || $brand_slug === '' || $category_slug === '' || empty($packs)) {
        continue;
    }

    $seed_key = 'seed-catalog-' . $slug;
    $product_id = 0;

    $by_meta = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_seed_catalog_key',
        'meta_value' => $seed_key,
    ]);
    if (!empty($by_meta)) {
        $product_id = (int) $by_meta[0];
    }

    if ($product_id <= 0) {
        $by_slug = get_posts([
            'post_type' => 'product',
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'fields' => 'ids',
            'name' => $slug,
        ]);
        if (!empty($by_slug)) {
            $product_id = (int) $by_slug[0];
        }
    }

    if ($product_id > 0) {
        $product = wc_get_product($product_id);
        if (!$product instanceof WC_Product) {
            $stats['errors']++;
            continue;
        }
        $stats['updated']++;
    } else {
        $product = new WC_Product_Simple();
        $product->set_status('publish');
        $product->set_catalog_visibility('visible');
        $product->set_manage_stock(true);
        $product_id = $product->save();
        if ($product_id <= 0) {
            $stats['errors']++;
            continue;
        }
        $stats['created']++;
    }

    $product->set_name($name);
    $product->set_slug($slug);
    $product->set_description($desc);
    $product->set_short_description('');
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_manage_stock(true);
    $product->set_stock_quantity($stock_qty);
    $product->set_stock_status($stock_qty > 0 ? 'instock' : 'outofstock');

    $capacity_labels = [];
    $weight_labels = [];
    $map_parts = [];
    $min_price = 0.0;

    foreach ($packs as $pack) {
        if (!is_array($pack) || count($pack) < 2) {
            continue;
        }
        $label = trim((string) $pack[0]);
        $price = (float) $pack[1];
        if ($label === '' || $price <= 0) {
            continue;
        }

        $map_parts[] = $label . ':' . $price;
        if ($min_price <= 0 || $price < $min_price) {
            $min_price = $price;
        }

        if (stripos($label, 'kg') !== false) {
            $weight_labels[] = $label;
        } else {
            $capacity_labels[] = $label;
        }
    }

    $capacity_labels = array_values(array_unique($capacity_labels));
    $weight_labels = array_values(array_unique($weight_labels));

    if ($min_price > 0) {
        $product->set_regular_price((string) $min_price);
        $product->set_price((string) $min_price);
    }

    if (!empty($map_parts)) {
        $product->update_meta_data('_capacity_price_map', implode(' | ', $map_parts));
    } else {
        $product->delete_meta_data('_capacity_price_map');
    }

    if (!empty($capacity_labels)) {
        $product->update_meta_data('_display_capacity_list', implode(' | ', $capacity_labels));
    } else {
        $product->delete_meta_data('_display_capacity_list');
    }

    if (!empty($weight_labels)) {
        $product->update_meta_data('_display_weight_list', implode(' | ', $weight_labels));
        if (preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string) $weight_labels[0], $m) === 1) {
            $product->set_weight((string) ((float) $m[1]));
        }
    } else {
        $product->delete_meta_data('_display_weight_list');
        $product->set_weight('');
    }

    $cat_term = get_term_by('slug', $category_slug, 'product_cat');
    if ($cat_term instanceof WP_Term) {
        wp_set_object_terms($product_id, [(int) $cat_term->term_id], 'product_cat', false);
    }

    if ($taxonomy_brand !== '') {
        $brand_label = $brand_labels[$brand_slug] ?? ucfirst($brand_slug);
        $brand_term_slug = ($brand_slug === 'nippon') ? 'nippon' : $brand_slug;
        $brand_term_id = $ensure_term($brand_label, $taxonomy_brand, $brand_term_slug);
        if ($brand_term_id > 0) {
            wp_set_object_terms($product_id, [(int) $brand_term_id], $taxonomy_brand, false);
        }
    }

    if ($taxonomy_line !== '') {
        $line_slug = function_exists('my_theme_detect_line_slug_from_text')
            ? my_theme_detect_line_slug_from_text($name . ' ' . $desc, $brand_slug)
            : '';
        $line_slug = sanitize_title((string) $line_slug);
        if ($line_slug === '') {
            $fallback_line_by_cat = [
                'son-lot' => 'line-primer',
                'chong-tham' => 'line-waterproof',
                'bot-tret' => 'line-putty',
                'son-noi-that' => 'line-interior',
                'son-ngoai-that' => 'line-exterior',
                'son-kim-loai' => 'line-metal',
                'son-epoxy' => 'line-epoxy',
                'son-cong-nghiep' => 'line-industrial',
                'keo-va-phu-gia' => 'line-adhesive',
                'son-dau' => 'line-oil',
            ];
            $line_slug = isset($fallback_line_by_cat[$category_slug])
                ? (string) $fallback_line_by_cat[$category_slug]
                : '';
        }
        if ($line_slug !== '') {
            $line_label = function_exists('my_theme_get_line_label_from_slug')
                ? my_theme_get_line_label_from_slug($line_slug)
                : ucwords(str_replace('-', ' ', $line_slug));
            $line_term_id = $ensure_term($line_label, $taxonomy_line, $line_slug);
            if ($line_term_id > 0) {
                wp_set_object_terms($product_id, [(int) $line_term_id], $taxonomy_line, false);
            }
        }
    }

    $seed_image_version = 'v3';
    $current_image_version = (string) get_post_meta($product_id, '_seed_image_version', true);
    $should_refresh_image = ((int) get_post_thumbnail_id($product_id) <= 0) || ($current_image_version !== $seed_image_version);
    if ($should_refresh_image) {
        $source_file = $pick_seed_image_file($brand_slug, $slug);
        if ($source_file !== '') {
            $img_id = (int) $attachment_for_file($source_file, $brand_slug . '-' . $slug);
            if ($img_id > 0) {
                set_post_thumbnail($product_id, $img_id);
                update_post_meta($product_id, '_seed_image_version', $seed_image_version);
            }
        }
    }

    $product->update_meta_data('_seed_catalog_key', $seed_key);
    $product->update_meta_data('_seed_catalog_brand', $brand_slug);
    $product->update_meta_data('_seed_catalog_source', 'tools/import_multibrand_seed_catalog.php');
    $product->save();
}

$legacy_apollo_slugs = ['a300', 'a500', 'a600', 'a800', 'a1000'];
foreach ($legacy_apollo_slugs as $legacy_slug) {
    $legacy_ids = get_posts([
        'post_type' => 'product',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'posts_per_page' => -1,
        'fields' => 'ids',
        'name' => sanitize_title($legacy_slug),
    ]);
    foreach ((array) $legacy_ids as $legacy_id) {
        $legacy_id = (int) $legacy_id;
        if ($legacy_id <= 0) {
            continue;
        }
        $seed_key = trim((string) get_post_meta($legacy_id, '_seed_catalog_key', true));
        if ($seed_key !== '') {
            continue;
        }
        $legacy_product = wc_get_product($legacy_id);
        if (!$legacy_product instanceof WC_Product) {
            continue;
        }
        $legacy_price = (float) $legacy_product->get_price();
        if ($legacy_price > 0) {
            continue;
        }
        if ($legacy_product->get_status() !== 'draft') {
            $legacy_product->set_status('draft');
        }
        $legacy_product->set_catalog_visibility('hidden');
        $legacy_product->update_meta_data('_seed_catalog_note', 'auto_hidden_legacy_apollo_sample');
        $legacy_product->save();
    }
}

if (function_exists('my_theme_flush_product_cache_fragments')) {
    my_theme_flush_product_cache_fragments(0);
}
update_option('my_theme_filter_cache_version', (string) time(), false);

if (function_exists('my_theme_get_catalog_visible_product_ids') && function_exists('my_theme_get_brand_filter_options')) {
    $visible_ids = my_theme_get_catalog_visible_product_ids(false);
    $brand_options = my_theme_get_brand_filter_options($visible_ids);
    if (function_exists('my_theme_filter_product_ids_by_brand_slug') && function_exists('my_theme_get_line_filter_options')) {
        foreach (array_keys((array) $brand_options) as $brand_slug) {
            $brand_ids = my_theme_filter_product_ids_by_brand_slug($visible_ids, (string) $brand_slug);
            my_theme_get_line_filter_options($brand_ids, (string) $brand_slug);
        }
    }
}

echo 'seed_import_done created=' . $stats['created'] . ' updated=' . $stats['updated'] . ' errors=' . $stats['errors'] . PHP_EOL;
