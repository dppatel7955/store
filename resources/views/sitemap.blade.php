<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach($staticPages as $page)
    <url>
        <loc>{{ $page['loc'] }}</loc>
        <changefreq>weekly</changefreq>
        <priority>{{ $page['priority'] }}</priority>
    </url>
@endforeach
@foreach($categories as $category)
    <url>
        <loc>{{ $category['loc'] }}</loc>
        @if(!empty($category['lastmod']))
        <lastmod>{{ $category['lastmod'] }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>{{ $category['priority'] }}</priority>
    </url>
@endforeach
@foreach($products as $product)
    <url>
        <loc>{{ $product['loc'] }}</loc>
        @if(!empty($product['lastmod']))
        <lastmod>{{ $product['lastmod'] }}</lastmod>
        @endif
        <changefreq>weekly</changefreq>
        <priority>{{ $product['priority'] }}</priority>
    </url>
@endforeach
</urlset>
