param(
    [switch]$IncludeNonSeed,
    [switch]$ForceDownload,
    [string]$OnlyBrand = ''
)

$ErrorActionPreference = 'Stop'

$OnlyBrand = ([string]$OnlyBrand).Trim().ToLowerInvariant()

$workspace = Split-Path -Parent $PSScriptRoot
$themeRoot = Join-Path $workspace 'wordpress\my-theme'
$imageDir = Join-Path $themeRoot 'assets\official-sync'
$mapPath = Join-Path $themeRoot 'data\official_image_map.json'

if (-not (Test-Path $themeRoot)) {
    throw "Theme root not found: $themeRoot"
}

if (-not (Test-Path $imageDir)) {
    New-Item -ItemType Directory -Path $imageDir -Force | Out-Null
}

function Get-AbsoluteUrl {
    param(
        [string]$BaseUrl,
        [string]$Url
    )
    if ([string]::IsNullOrWhiteSpace($Url)) {
        return ''
    }
    if ($Url -match '^https?://') {
        return $Url
    }
    try {
        $base = [System.Uri]$BaseUrl
        return ([System.Uri]::new($base, $Url)).AbsoluteUri
    } catch {
        return $Url
    }
}

function Get-RelativePathSafe {
    param(
        [string]$BasePath,
        [string]$TargetPath
    )
    try {
        $baseResolved = (Resolve-Path -LiteralPath $BasePath).Path
        $targetResolved = (Resolve-Path -LiteralPath $TargetPath).Path
        $baseUri = New-Object System.Uri(($baseResolved.TrimEnd('\') + '\'))
        $targetUri = New-Object System.Uri($targetResolved)
        $rel = $baseUri.MakeRelativeUri($targetUri).ToString()
        return [System.Uri]::UnescapeDataString($rel).Replace('/', '\')
    } catch {
        return $TargetPath
    }
}

function Normalize-Html {
    param([object]$Raw)
    if ($Raw -is [System.Array]) {
        return ($Raw -join "`n")
    }
    return [string]$Raw
}

function Invoke-WebHtml {
    param([string]$Url)
    if ([string]::IsNullOrWhiteSpace($Url)) {
        return ''
    }
    $raw = curl.exe -L -s --max-time 45 `
        -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123 Safari/537.36" `
        -H "Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7" `
        $Url
    return (Normalize-Html $raw)
}

function Extract-FirstMatch {
    param(
        [string]$Text,
        [string]$Pattern
    )
    $m = [regex]::Match([string]$Text, $Pattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if ($m.Success -and $m.Groups.Count -gt 1) {
        return [string]$m.Groups[1].Value
    }
    return ''
}

function Extract-TitleFromHtml {
    param([string]$Html)
    $title = Extract-FirstMatch -Text $Html -Pattern '<meta[^>]+property=["'']og:title["''][^>]+content=["'']([^"'']+)'
    if ([string]::IsNullOrWhiteSpace($title)) {
        $title = Extract-FirstMatch -Text $Html -Pattern '<meta[^>]+content=["'']([^"'']+)["''][^>]+property=["'']og:title["'']'
    }
    if ([string]::IsNullOrWhiteSpace($title)) {
        $title = Extract-FirstMatch -Text $Html -Pattern '<title[^>]*>([^<]+)</title>'
    }
    return $title.Trim()
}

function Extract-ImageFromHtml {
    param(
        [string]$Brand,
        [string]$PageUrl,
        [string]$Html
    )

    $img = ''

    if ($Brand -eq 'jotun') {
        $jotunMatch = [regex]::Matches($Html, 'https://cp\.jotun\.com/siteassets[^"''\s>]+')
        if ($jotunMatch.Count -gt 0) {
            $img = $jotunMatch[0].Value
        }
    }

    if ($Brand -eq 'nippon' -and [string]::IsNullOrWhiteSpace($img)) {
        $m1 = [regex]::Matches($Html, 'https://nipponpaint\.com\.vn/sites/default/files/[^"''\s>]+\.(jpg|jpeg|png|webp)')
        if ($m1.Count -gt 0) {
            $img = $m1[$m1.Count - 1].Value
        } else {
            $m2 = [regex]::Matches($Html, 'src=["''](/sites/default/files/[^"''\s>]+\.(jpg|jpeg|png|webp)[^"''\s>]*)["'']')
            if ($m2.Count -gt 0) {
                $img = 'https://nipponpaint.com.vn' + $m2[$m2.Count - 1].Groups[1].Value
            }
        }
    }

    if ($Brand -eq 'kova' -and [string]::IsNullOrWhiteSpace($img)) {
        $k1 = [regex]::Matches($Html, 'class=["'']image_product["'']\s+style=["'']background-image:url\([''"]?([^''")]+)')
        if ($k1.Count -gt 0) {
            $img = $k1[0].Groups[1].Value
        }
        if ([string]::IsNullOrWhiteSpace($img)) {
            $k2 = [regex]::Matches($Html, 'data-src=["'']([^"'']+)["'']')
            foreach ($hit in $k2) {
                $candidate = [string]$hit.Groups[1].Value
                if ($candidate -match '/upload/' -and $candidate -match '\.(jpg|jpeg|png|webp)') {
                    $img = $candidate
                    break
                }
            }
        }
    }

    if ($Brand -eq 'sika' -and [string]::IsNullOrWhiteSpace($img)) {
        $s1 = [regex]::Matches($Html, 'https://sika\.scene7\.com/is/image/sikacs/[^"''\s<>&]+')
        if ($s1.Count -gt 0) {
            $img = $s1[0].Value
        }
    }

    if ([string]::IsNullOrWhiteSpace($img)) {
        $img = Extract-FirstMatch -Text $Html -Pattern '<meta[^>]+property=["'']og:image["''][^>]+content=["'']([^"'']+)'
    }
    if ([string]::IsNullOrWhiteSpace($img)) {
        $img = Extract-FirstMatch -Text $Html -Pattern '<meta[^>]+content=["'']([^"'']+)["''][^>]+property=["'']og:image["'']'
    }
    if ([string]::IsNullOrWhiteSpace($img)) {
        $img = Extract-FirstMatch -Text $Html -Pattern '<meta[^>]+name=["'']twitter:image["''][^>]+content=["'']([^"'']+)'
    }
    if ([string]::IsNullOrWhiteSpace($img)) {
        $img = Extract-FirstMatch -Text $Html -Pattern '<img[^>]+src=["'']([^"'']+\.(jpg|jpeg|png|webp)[^"'']*)["'']'
    }

    $img = Get-AbsoluteUrl -BaseUrl $PageUrl -Url $img
    if ($img -match '(?i)(favicon|sprite)') {
        return ''
    }
    return $img
}

function Download-Image {
    param(
        [string]$ImageUrl,
        [string]$BaseName
    )
    if ([string]::IsNullOrWhiteSpace($ImageUrl)) {
        return ''
    }

    $cleanBase = ([string]$BaseName).ToLowerInvariant() -replace '[^a-z0-9\-_]+', '-'
    if ([string]::IsNullOrWhiteSpace($cleanBase)) {
        $cleanBase = 'official'
    }

    $ext = 'jpg'
    try {
        $uri = [System.Uri]$ImageUrl
        $pathExt = [System.IO.Path]::GetExtension($uri.AbsolutePath).TrimStart('.').ToLowerInvariant()
        if ($pathExt -in @('jpg', 'jpeg', 'png', 'webp', 'svg')) {
            $ext = $pathExt
        }
    } catch {}

    $target = Join-Path $imageDir ($cleanBase + '.' + $ext)
    if ((-not $ForceDownload) -and (Test-Path $target) -and ((Get-Item $target).Length -gt 1024)) {
        return $target
    }

    curl.exe -L -s --max-time 60 `
        -A "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123 Safari/537.36" `
        -o $target $ImageUrl | Out-Null

    if (-not (Test-Path $target)) {
        return ''
    }

    $size = (Get-Item $target).Length
    if ($size -lt 1024) {
        Remove-Item -Force $target -ErrorAction SilentlyContinue
        return ''
    }
    return $target
}

$brandLinePages = @{
    jotun = @{
        'default' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/essence-easy-clean'
        'essence' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/essence-easy-clean'
        'majestic' = 'https://www.jotun.com/vn-vi/decorative/interior/majestic-sense'
        'jotaplast' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/jotaplast'
        'jotashield' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotashield-antifade-colours'
        'waterguard' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotun-waterguard'
        'line-primer' = 'https://www.jotun.com/vn-vi/industries/products/majestic-primer'
        'line-metal' = 'https://www.jotun.com/vn-vi/industries/products/gardex-premium-gloss'
        'line-epoxy' = 'https://www.jotun.com/ww-en/industries/products/penguard-primer'
        'line-industrial' = 'https://www.jotun.com/ww-en/industries/products/jotamastic-87'
        'line-interior' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/essence-easy-clean'
        'line-exterior' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotashield-antifade-colours'
    }
    nippon = @{
        'default' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-odour-less-all-1'
        'odourless' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-odour-less-all-1'
        'weatherbond' = 'https://nipponpaint.com.vn/vi/son-ngoai-that/son-nippon-weathergard-plus'
        'skimcoat' = 'https://nipponpaint.com.vn/vi/son-noi-that/skimcoat-noi-that'
        'vinilex' = 'https://nipponpaint.com.vn/vi/son-dan-dung/son-nippon-vinilex-130-active-primer'
        'matex' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-matex'
        'line-primer' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-lot-noi-that-odour-less-sealer'
        'line-waterproof' = 'https://nipponpaint.com.vn/vi/son-ngoai-that/wp-100-chong-tham'
        'line-oil' = 'https://nipponpaint.com.vn/vi/son-dan-dung/son-nippon-tilac'
        'line-interior' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-odour-less-all-1'
        'line-exterior' = 'https://nipponpaint.com.vn/vi/son-ngoai-that/son-nippon-weathergard-plus'
    }
    kova = @{
        'default' = 'https://www.kovapaint.com/son-noi-that-cao-cap-kova-k-871'
        'ct11a-plus' = 'https://www.kovapaint.com/chat-chong-tham-cao-cap-kova-ct-11a-plus-tuong'
        'k209' = 'https://www.kovapaint.com/son-lot-ngoai-that-khang-kiem-kova-k-209'
        'k261' = 'https://www.kovapaint.com/son-ngoai-that-kova-k-261-plus'
        'k871' = 'https://www.kovapaint.com/son-noi-that-cao-cap-kova-k-871'
        'k5501' = 'https://www.kovapaint.com/son-ngoai-that-cao-cap-kova-k-5501-plus'
        'line-putty' = 'https://www.kovapaint.com/bot-tret-noi-that-kova-dx'
        'line-waterproof' = 'https://www.kovapaint.com/chong-tham-mau-kova-ct-11a-color'
        'line-primer' = 'https://www.kovapaint.com/son-lot-noi-that-khang-kiem-kova-k-109'
        'line-metal' = 'https://www.kovapaint.com/son-chong-gi-he-nuoc-kova-kg-01'
        'line-epoxy' = 'https://www.kovapaint.com/son-cong-nghiep-kova-epoxy-kl-5-san'
        'line-interior' = 'https://www.kovapaint.com/son-noi-that-cao-cap-kova-k-871'
        'line-exterior' = 'https://www.kovapaint.com/son-ngoai-that-kova-k-261-plus'
    }
    toa = @{
        'default' = 'https://www.toagroup.com.vn'
        'supershield' = 'https://www.toagroup.com.vn/search?keyword=supershield'
        'nanoshield' = 'https://www.toagroup.com.vn/search?keyword=nanoshield'
        '4seasons' = 'https://www.toagroup.com.vn/search?keyword=4%20seasons'
        'toa1000' = 'https://www.toagroup.com.vn/search?keyword=toa%201000'
        'rusttech' = 'https://www.toagroup.com.vn/search?keyword=rust%20tech'
        'line-waterproof' = 'https://www.toagroup.com.vn/search?keyword=waterproof'
        'line-putty' = 'https://www.toagroup.com.vn/search?keyword=bot%20tret%20toa'
        'line-primer' = 'https://www.toagroup.com.vn/search?keyword=toa%201000'
        'line-epoxy' = 'https://www.toagroup.com.vn/search?keyword=epoxy'
        'line-industrial' = 'https://www.toagroup.com.vn/search?keyword=weatherkote'
        'line-interior' = 'https://www.toagroup.com.vn/search?keyword=supershield'
        'line-exterior' = 'https://www.toagroup.com.vn/search?keyword=nanoshield'
    }
    sika = @{
        'default' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/chong-tham/v-a-ch-ng-th-m/sikatop-107-sealvn.html'
        'sikatop' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/chong-tham/v-a-ch-ng-th-m/sikatop-107-sealvn.html'
        'sikalatex' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/sikalatex-th.html'
        'sikafloor' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/ph-san/sikafloor-263-slhc.html'
        'sikaceram' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/xay-d-ng-hoan-thin/keo-dan-g-ch/sikaceram-200-hp.html'
        'sikagrout' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/sikagrout-214-11.html'
        'sikaflex' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/tram-khe-k-t-dinh/sikaflex-11-fc.html'
        'sikaguard' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/xay-d-ng-hoan-thin/khac/sikagard-905-w.html'
        'monotop' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/v-a-s-a-ch-a-p-th-ng/sika-monotop-615-hb.html'
        'line-primer' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/tram-khe-k-t-dinh/sika-primer-3-n.html'
        'line-waterproof' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/chong-tham/v-a-ch-ng-th-m/sikatop-107-sealvn.html'
        'line-adhesive' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/xay-d-ng-hoan-thin/keo-dan-g-ch/sikaceram-200-hp.html'
        'line-epoxy' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/ph-san/sikafloor-263-slhc.html'
        'line-industrial' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/ph-san/sikafloor-263-slhc.html'
    }
    weber = @{
        'default' = 'https://www.vn.weber/vi/webercolor-no-stain'
        'webercolor' = 'https://www.vn.weber/vi/webercolor-no-stain'
        'webertai' = 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres'
        'webertec' = 'https://www.vn.weber/vi/webertec-grout-60'
        'weberdry' = 'https://www.vn.weber/vi/weberdry-2kflex'
        'weberseal' = 'https://www.vn.weber/vi/weberseal-ws300'
        'weberprime' = 'https://www.vn.weber/vi/weberprime-spf-11'
        'weberepox' = 'https://www.vn.weber/vi/keo-epoxy'
        'webershield' = 'https://www.vn.weber/vi/webershield-320'
        'line-adhesive' = 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg'
        'line-waterproof' = 'https://www.vn.weber/vi/weberdry-2kflex'
    }
    apollo = @{
        'default' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a500'
        'a100' = 'https://apollosilicone.vn/san-pham/apollo-sealant-acrylic-a100'
        'a200' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a200'
        'a300' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a300'
        'a500' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a500'
        'a600' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a600'
        'sanitary-n' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-sanitary-n'
        'weatherseal-a68' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-weatherseal-a68'
        'weatherseal-a79' = 'https://apollosilicone.vn/san-pham/keo-silicon-thoi-tiet-cao-cap-apollo-silicone-sealant-weatherseal-a79'
        'pu-foam' = 'https://apollosilicone.vn/san-pham/apollo-foam'
        'pu-foam-b1' = 'https://apollosilicone.vn/san-pham/gioi-thieu-ve-san-pham-chuyen-dung-apollo-foam-b1'
        'line-adhesive' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a500'
        'line-waterproof' = 'https://apollosilicone.vn/san-pham/keo-silicon-thoi-tiet-cao-cap-apollo-silicone-sealant-weatherseal-a79'
        'line-putty' = 'https://apollosilicone.vn/san-pham/apollo-sealant-acrylic-a100'
    }
}

$slugPageOverrides = @{
    jotun = @{
        'jotun-majestic-primer-5l' = 'https://www.jotun.com/vn-vi/industries/products/majestic-primer'
        'jotashield-ben-mau-5l' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotashield-antifade-colours'
        'jotun-waterguard-4l' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotun-waterguard'
        'jotun-majestic-silk-5l' = 'https://www.jotun.com/vn-vi/decorative/interior/majestic-sense'
        'jotun-essence-de-lau-chui-5l' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/essence-easy-clean'
        'jotun-jotaplast-noi-that-18l' = 'https://www.jotun.com/vn-vi/jotun/decorative/interior/products/jotaplast'
        'jotashield-clean-extreme-5l' = 'https://www.jotun.com/vn-vi/jotun/decorative/exterior/products/jotashield-clean-extreme'
        'jotun-gardex-metal-primer-0-8l' = 'https://www.jotun.com/vn-vi/industries/products/gardex-premium-gloss'
        'jotun-penguard-primer-5l' = 'https://www.jotun.com/ww-en/industries/products/penguard-primer'
        'jotun-jotamastic-87-20l' = 'https://www.jotun.com/ww-en/industries/products/jotamastic-87'
    }
    nippon = @{
        'nippon-odourless-5l' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-odour-less-all-1'
        'nippon-weatherbond-5l' = 'https://nipponpaint.com.vn/vi/son-ngoai-that/son-nippon-weathergard-plus'
        'nippon-skim-coat-40kg' = 'https://nipponpaint.com.vn/vi/son-noi-that/skimcoat-noi-that'
        'nippon-vinilex-5000-5l' = 'https://nipponpaint.com.vn/vi/son-dan-dung/son-nippon-vinilex-130-active-primer'
        'nippon-super-matex-5l' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-matex'
        'nippon-matex-sieu-trang-5l' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-matex'
        'nippon-odourless-easywash-5l' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-nippon-odour-less-all-1'
        'nippon-hydroshield-5l' = 'https://nipponpaint.com.vn/vi/son-ngoai-that/wp-100-chong-tham'
        'nippon-exterior-sealer-5l' = 'https://nipponpaint.com.vn/vi/son-noi-that/son-lot-noi-that-odour-less-sealer'
        'nippon-bodelac-9000-0-9l' = 'https://nipponpaint.com.vn/vi/son-dan-dung/son-nippon-tilac'
    }
    weber = @{
        'weberad-latex' = 'https://www.vn.weber/vi/weberad-latex'
        'weberepox-easy' = 'https://www.vn.weber/vi/weberepox-easy'
        'weberprime-epox' = 'https://www.vn.weber/vi/weberprime-epox-094'
        'weberprime-spf' = 'https://www.vn.weber/vi/weberprime-spf-11'
        'weberproof-hdpe' = 'https://www.vn.weber/vi/weberproof-hdpe'
        'weberproof-tpo' = 'https://www.vn.weber/vi/weberproof-tpo'
        'weberseal-wa100' = 'https://www.vn.weber/vi/weberseal-wa100'
        'weberseal-ws300' = 'https://www.vn.weber/vi/weberseal-ws300'
        'weberseal-ws500' = 'https://www.vn.weber/vi/weberseal-ws500'
        'webershield' = 'https://www.vn.weber/vi/webershield-320'
        'webertai-fix' = 'https://www.vn.weber/vi/webertai-fix'
        'webertai-flex' = 'https://www.vn.weber/vi/webertai-flex'
        'webertai-gres' = 'https://www.vn.weber/vi/keo-dan-gach-webertai-gres-40kg'
        'webertai-st250' = 'https://www.vn.weber/vi/webertai-ST250'
        'webertai-vis' = 'https://www.vn.weber/vi/keo-dan-gach-webertai-vis-40kg'
    }
    kova = @{
        'kova-k209-20kg' = 'https://www.kovapaint.com/son-lot-ngoai-that-khang-kiem-kova-k-209'
        'kova-ct11a-plus-5l' = 'https://www.kovapaint.com/chat-chong-tham-cao-cap-kova-ct-11a-plus-tuong'
        'kova-bot-tret-noi-that-40kg' = 'https://www.kovapaint.com/bot-tret-noi-that-kova-dx'
        'kova-ct11a-san-thuong-20kg' = 'https://www.kovapaint.com/chong-tham-mau-kova-ct-11a-color'
        'kova-k261-son-lot-khang-kiem-20kg' = 'https://www.kovapaint.com/son-ngoai-that-kova-k-261-plus'
        'kova-k871-ngoai-that-5l' = 'https://www.kovapaint.com/son-noi-that-cao-cap-kova-k-871'
        'kova-k5501-noi-that-5l' = 'https://www.kovapaint.com/son-ngoai-that-cao-cap-kova-k-5501-plus'
        'kova-matit-deo-ngoai-that-40kg' = 'https://www.kovapaint.com/bot-tret-noi-that-kova-dx'
        'kova-son-kim-loai-metal-primer-0-8l' = 'https://www.kovapaint.com/son-chong-gi-he-nuoc-kova-kg-01'
        'kova-son-epoxy-san-cong-nghiep-20kg' = 'https://www.kovapaint.com/son-cong-nghiep-kova-epoxy-kl-5-san'
    }
    sika = @{
        'sikalatex-th-5l' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/sikalatex-th.html'
        'sikatop-seal-107-25kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/chong-tham/v-a-ch-ng-th-m/sikatop-107-sealvn.html'
        'sikaguard-905w-5l' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/xay-d-ng-hoan-thin/khac/sikagard-905-w.html'
        'sikafloor-263-20kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/ph-san/sikafloor-263-slhc.html'
        'sikafloor-81-epocem-25kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/ph-san/sikafloor-263-slhc.html'
        'sikaceram-200-tilefix-25kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/xay-d-ng-hoan-thin/keo-dan-g-ch/sikaceram-200-hp.html'
        'sikagrout-214-25kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/sikagrout-214-11.html'
        'sika-monotop-615-25kg' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/s-a-ch-a/v-a-s-a-ch-a-p-th-ng/sika-monotop-615-hb.html'
        'sikaflex-construction-600ml' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/tram-khe-k-t-dinh/sikaflex-11-fc.html'
        'sika-primer-3n-1l' = 'https://vnm.sika.com/vi/kenh-phan-ph-i-banl/tram-khe-k-t-dinh/sika-primer-3-n.html'
    }
    toa = @{
        'toa-supershield-noi-that-de-lau-chui-5l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-nuoc-noi-that-sieu-cao-cap-supershield-duraclean-1'
        'toa-supershield-ngoai-that-ben-mau-5l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-nuoc-ngoai-that-sieu-cao-cap-supershield'
        'toa-4seasons-ngoai-that-5l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-nuoc-ngoai-that-toa-4-seasons-satin-glo'
        'toa-nanoshield-noi-that-5l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-nuoc-ngoai-that-cao-cap-toa-nanoshield-2'
        'toa-1000-lot-khang-kiem-5l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/toa-4-seasons-sealer'
        'toa-waterproof-201-20kg' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/son-chong-tham-mau-toa-waterblock-color'
        'toa-bot-tret-noi-that-40kg' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/bot-tret-tuong-noi-that-toa-wall-mastic-interior'
        'toa-rust-tech-kim-loai-primer-0-8l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/rust-tech-son-lot-phu'
        'toa-epoxy-floor-topcoat-20kg' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/heavy-guard---son-phu-bong-noi-that-epoguard-enamel-1'
        'toa-cong-nghiep-weatherproof-18l' = 'https://www.toagroup.com.vn/san-pham-chi-tiet/chong-tham-bitum-toa-weatherkote-no3'
    }
    apollo = @{
        'apollo-acrylic-sealant-a100' = 'https://apollosilicone.vn/san-pham/apollo-sealant-acrylic-a100'
        'apollo-silicone-sealant-a200' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a200'
        'apollo-silicone-sealant-a300' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a300'
        'apollo-silicone-sealant-a500' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a500'
        'apollo-silicone-sealant-a600' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-a600'
        'apollo-silicone-sealant-sanitary-n' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-sanitary-n'
        'apollo-silicone-weatherseal-a68' = 'https://apollosilicone.vn/san-pham/keo-silicon-apollo-silicone-sealant-weatherseal-a68'
        'apollo-silicone-weatherseal-a79' = 'https://apollosilicone.vn/san-pham/keo-silicon-thoi-tiet-cao-cap-apollo-silicone-sealant-weatherseal-a79'
        'apollo-pu-foam' = 'https://apollosilicone.vn/san-pham/apollo-foam'
        'apollo-pu-foam-b1' = 'https://apollosilicone.vn/san-pham/gioi-thieu-ve-san-pham-chuyen-dung-apollo-foam-b1'
    }
}

$slugImageOverrides = @{
    jotun = @{
        'jotun-jotamastic-87-20l' = 'https://www.jotun.com/globalassets-b2b/b2b/.-shared-across-pages/jotuncan_background.png?width=1437&quality=70'
        'jotashield-clean-extreme-5l' = 'https://www.jotun.com/globalassetsjot03/200687-desktop.png'
        'jotun-penguard-primer-5l' = 'https://www.jotun.com/globalassets-b2b/b2b/.-shared-across-pages/jotuncan_background.png?width=1437&quality=70'
        'jotun-gardex-metal-primer-0-8l' = 'https://www.jotun.com/globalassets-b2b/b2b/.-shared-across-pages/jotuncan_background.png?width=1437&quality=70'
        'jotun-majestic-primer-5l' = 'https://www.jotun.com/globalassets-b2b/b2b/.-shared-across-pages/jotuncan_background.png?width=1437&quality=70'
    }
    sika = @{
        'sikafloor-263-20kg' = 'https://sika.scene7.com/is/image/sikacs/ro-02-sikafloor-263-sl-n-1x1-01335594%3A1-1?fit=crop%2C1&hei=480&wid=480'
        'sikafloor-81-epocem-25kg' = 'https://sika.scene7.com/is/image/sikacs/ro-02-sikafloor-263-sl-n-1x1-01335594%3A1-1?fit=crop%2C1&hei=480&wid=480'
        'sikaflex-construction-600ml' = 'https://sika.scene7.com/is/image/sikacs/ro-02-sikaflex-11fc-purform-300ml-white-1x1-01931305?fit=crop%2C1&wid=480'
        'sika-primer-3n-1l' = 'https://sika.scene7.com/is/image/sikacs/ro-02-sika-primer-3n-1x1-01820687%3A1-1?fit=crop%2C1&hei=480&wid=480'
        'sika-monotop-615-25kg' = 'https://sika.scene7.com/is/image/sikacs/vn-02%E2%80%90en-VN-Sika-Monotop-615-HB-1x1-00509816:1-1?fit=crop,1&hei=480&wid=480'
    }
    toa = @{
        'toa-supershield-noi-that-de-lau-chui-5l' = 'https://www.toagroup.com.vn/uploads/product/3d2750d14788b7-supershieldduraclean.jpg'
        'toa-supershield-ngoai-that-ben-mau-5l' = 'https://www.toagroup.com.vn/uploads/product/765c70e41bf9c3-supershield_phienban2.png'
        'toa-4seasons-ngoai-that-5l' = 'https://www.toagroup.com.vn/uploads/product/fd9ec3f7d65c96-sonngoaithattoa4seasonssatinglo.png'
        'toa-nanoshield-noi-that-5l' = 'https://www.toagroup.com.vn/uploads/product/45159abe841b65-toananoshield_10nambaove_phienban2.png'
        'toa-1000-lot-khang-kiem-5l' = 'https://www.toagroup.com.vn/uploads/product/f5499d3d6758d5-sonlotchongkiemngoaithattoa4seasonssealer.png'
        'toa-waterproof-201-20kg' = 'https://www.toagroup.com.vn/uploads/product/209cbef29adcc5-ctmaunew.png'
        'toa-bot-tret-noi-that-40kg' = 'https://www.toagroup.com.vn/uploads/product/80e264dee8e418-bottretwallmasticintnew.png'
        'toa-rust-tech-kim-loai-primer-0-8l' = 'https://www.toagroup.com.vn/uploads/product/b9b837f638b397-rusttech.png'
        'toa-epoxy-floor-topcoat-20kg' = 'https://www.toagroup.com.vn/uploads/product/0b2e9c9e193a5d-epoguardenamel.png'
        'toa-cong-nghiep-weatherproof-18l' = 'https://www.toagroup.com.vn/uploads/product/69ee5eeb264964-toaweatherkote.png'
    }
}

$pageCache = @{}

function Get-PageMeta {
    param(
        [string]$Brand,
        [string]$PageUrl
    )
    if ([string]::IsNullOrWhiteSpace($PageUrl)) {
        return [ordered]@{
            title = ''
            image = ''
        }
    }

    if ($pageCache.ContainsKey($PageUrl)) {
        return $pageCache[$PageUrl]
    }

    $html = Invoke-WebHtml -Url $PageUrl
    $title = ''
    $image = ''
    if (-not [string]::IsNullOrWhiteSpace($html)) {
        $title = Extract-TitleFromHtml -Html $html
        $image = Extract-ImageFromHtml -Brand $Brand -PageUrl $PageUrl -Html $html
    }

    $meta = [ordered]@{
        title = [string]$title
        image = [string]$image
    }
    $pageCache[$PageUrl] = $meta
    return $meta
}

$productProbe = @'
<?php
require '/var/www/html/wp-load.php';
$ids = function_exists('my_theme_get_catalog_visible_product_ids')
  ? my_theme_get_catalog_visible_product_ids(false)
  : get_posts([
      'post_type' => 'product',
      'post_status' => 'publish',
      'posts_per_page' => -1,
      'fields' => 'ids',
      'no_found_rows' => true,
    ]);
$rows = [];
foreach ((array) $ids as $id) {
    $id = (int) $id;
    if ($id <= 0) {
        continue;
    }
    $p = wc_get_product($id);
    if (!$p instanceof WC_Product) {
        continue;
    }
    if (function_exists('my_theme_is_shop_visible_product') && !my_theme_is_shop_visible_product($p)) {
        continue;
    }
    $rows[] = [
        'id' => $id,
        'slug' => $p->get_slug(),
        'name' => $p->get_name(),
        'brand' => function_exists('my_theme_get_product_brand_slug') ? my_theme_get_product_brand_slug($p) : '',
        'line' => function_exists('my_theme_get_product_line_slug') ? my_theme_get_product_line_slug($p) : '',
        'seed_key' => (string) get_post_meta($id, '_seed_catalog_key', true),
    ];
}
echo json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
'@

$productJson = $productProbe | docker exec -i lephat1898-wordpress-1 php
if ([string]::IsNullOrWhiteSpace($productJson)) {
    throw 'Cannot read product list from WordPress container.'
}

$parsedProducts = $productJson | ConvertFrom-Json
if ($parsedProducts -is [System.Array]) {
    $products = $parsedProducts
} elseif ($null -eq $parsedProducts) {
    $products = @()
} else {
    $products = @($parsedProducts)
}
if ($products.Count -eq 0) {
    throw 'No products found to map.'
}

$result = @()
$stats = [ordered]@{
    total = 0
    selected = 0
    mapped = 0
    downloaded = 0
    skipped = 0
}
$brandStats = @{}

foreach ($p in $products) {
    $stats.total++

    $brand = [string]$p.brand
    $line = [string]$p.line
    $slug = [string]$p.slug
    $seedKey = [string]$p.seed_key

    if (-not [string]::IsNullOrWhiteSpace($OnlyBrand) -and $brand -ne $OnlyBrand) {
        continue
    }

    if ([string]::IsNullOrWhiteSpace($brand) -or -not $brandLinePages.ContainsKey($brand)) {
        $stats.skipped++
        continue
    }

    if ((-not $IncludeNonSeed) -and [string]::IsNullOrWhiteSpace($seedKey)) {
        $stats.skipped++
        continue
    }

    $stats.selected++
    if (-not $brandStats.ContainsKey($brand)) {
        $brandStats[$brand] = [ordered]@{
            selected = 0
            mapped = 0
            skipped = 0
        }
    }
    $brandStats[$brand]['selected']++

    $pageUrl = ''
    if ($slugPageOverrides.ContainsKey($brand)) {
        $brandSlugMap = $slugPageOverrides[$brand]
        if ($brandSlugMap.ContainsKey($slug)) {
            $pageUrl = [string]$brandSlugMap[$slug]
        }
    }
    if ([string]::IsNullOrWhiteSpace($pageUrl)) {
        $lineMap = $brandLinePages[$brand]
        if (-not [string]::IsNullOrWhiteSpace($line) -and $lineMap.ContainsKey($line)) {
            $pageUrl = [string]$lineMap[$line]
        } elseif ($lineMap.ContainsKey('default')) {
            $pageUrl = [string]$lineMap['default']
        }
    }
    if ([string]::IsNullOrWhiteSpace($pageUrl)) {
        $stats.skipped++
        $brandStats[$brand]['skipped']++
        continue
    }

    $meta = Get-PageMeta -Brand $brand -PageUrl $pageUrl
    $imageUrl = ''
    if ($slugImageOverrides.ContainsKey($brand)) {
        $brandImageMap = $slugImageOverrides[$brand]
        if ($brandImageMap.ContainsKey($slug)) {
            $imageUrl = [string]$brandImageMap[$slug]
        }
    }
    if ([string]::IsNullOrWhiteSpace($imageUrl)) {
        $imageUrl = [string]$meta.image
    }
    $imageUrl = Get-AbsoluteUrl -BaseUrl $pageUrl -Url $imageUrl
    if ([string]::IsNullOrWhiteSpace($imageUrl)) {
        $stats.skipped++
        $brandStats[$brand]['skipped']++
        continue
    }

    $baseName = ($brand + '_' + ($slug -replace '[^a-zA-Z0-9\-_]', '-')).ToLowerInvariant()
    $localFile = Download-Image -ImageUrl $imageUrl -BaseName $baseName
    if ([string]::IsNullOrWhiteSpace($localFile)) {
        $stats.skipped++
        $brandStats[$brand]['skipped']++
        continue
    }

    $stats.downloaded++
    $stats.mapped++
    $brandStats[$brand]['mapped']++

    $relFromTheme = Get-RelativePathSafe -BasePath $themeRoot -TargetPath $localFile
    $relFromTheme = [string]$relFromTheme
    $relFromTheme = $relFromTheme.Replace('\', '/')

    $result += [ordered]@{
        product_id = [int]$p.id
        slug = $slug
        name = [string]$p.name
        brand = $brand
        line = $line
        page_url = $pageUrl
        image_url = $imageUrl
        official_title = [string]$meta.title
        local_file = $relFromTheme
    }
}

$retainedExisting = 0
if (-not [string]::IsNullOrWhiteSpace($OnlyBrand) -and (Test-Path $mapPath)) {
    try {
        $existingRaw = Get-Content -Raw -Path $mapPath -ErrorAction Stop
        if (-not [string]::IsNullOrWhiteSpace($existingRaw)) {
            $existingParsed = $existingRaw | ConvertFrom-Json
            $existingRows = @()
            if ($existingParsed -is [System.Array]) {
                $existingRows = $existingParsed
            } elseif ($null -ne $existingParsed) {
                $existingRows = @($existingParsed)
            }

            if ($existingRows.Count -gt 0) {
                $kept = @()
                foreach ($row in $existingRows) {
                    $existingBrand = ''
                    try {
                        $existingBrand = ([string]$row.brand).Trim().ToLowerInvariant()
                    } catch {
                        $existingBrand = ''
                    }
                    if ($existingBrand -ne $OnlyBrand) {
                        $kept += $row
                    }
                }

                $retainedExisting = $kept.Count
                $result = @($kept + $result)
            }
        }
    } catch {
        Write-Warning ("Cannot merge existing map file: " + $_.Exception.Message)
    }
}

$json = $result | ConvertTo-Json -Depth 8
$utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[System.IO.File]::WriteAllText($mapPath, $json, $utf8NoBom)

Write-Output ("official_image_map_done total={0} selected={1} mapped={2} downloaded={3} skipped={4}" -f $stats.total, $stats.selected, $stats.mapped, $stats.downloaded, $stats.skipped)
if (-not [string]::IsNullOrWhiteSpace($OnlyBrand)) {
    Write-Output ("only_brand=" + $OnlyBrand)
    Write-Output ("retained_existing_rows=" + $retainedExisting)
}
Write-Output ("map_file=" + $mapPath)
Write-Output ("image_dir=" + $imageDir)
foreach ($brandName in ($brandStats.Keys | Sort-Object)) {
    $bs = $brandStats[$brandName]
    Write-Output ("brand={0} selected={1} mapped={2} skipped={3}" -f $brandName, $bs.selected, $bs.mapped, $bs.skipped)
}
