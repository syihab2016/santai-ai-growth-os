<?php
if (!defined('ABSPATH')) exit;

function smos_get_platforms()
{
    return array(
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'youtube_community' => 'YouTube Community'
    );
}

function smos_get_templates()
{
    return array(
        'hard_sell' => 'Hard Sell',
        'soft_sell' => 'Soft Sell',
        'story' => 'Storytelling',
        'problem_solution' => 'Problem Solution',
        'educational' => 'Educational',
        'review' => 'Review/Testimoni',
        'aida' => 'AIDA',
        'pas' => 'PAS',
        'question' => 'Question Hook'
    );
}

function smos_get_lengths()
{
    return array('short' => 'Pendek', 'medium' => 'Sederhana', 'long' => 'Panjang');
}

function smos_get_emoji_levels()
{
    return array('none' => 'Tiada', 'low' => 'Sedikit', 'medium' => 'Sederhana', 'high' => 'Banyak');
}

function smos_get_cta_types()
{
    return array('website' => 'Website', 'whatsapp' => 'WhatsApp', 'messenger' => 'Messenger', 'shopee' => 'Shopee', 'tiktok_shop' => 'TikTok Shop');
}

function smos_build_prompt($args)
{
    $data = $args['product'];
    $platform = $args['platform'];
    $template = $args['template'];
    $length = $args['length'];
    $emoji = $args['emoji'];
    $cta_type = $args['cta_type'];
    $angle = $args['angle'];
    $extra = $args['extra_instruction'];

    $template_rules = array(
        'hard_sell' => 'Fokus kepada tawaran, harga promosi, urgency dan CTA jelas.',
        'soft_sell' => 'Fokus kepada kesedaran, manfaat dan promosi secara lembut.',
        'story' => 'Mulakan dengan cerita ringkas yang dekat dengan pembaca.',
        'problem_solution' => 'Mulakan dengan masalah pembeli, kemudian tunjukkan produk sebagai penyelesaian.',
        'educational' => 'Mulakan dengan ilmu/nasihat, kemudian sambungkan kepada produk.',
        'review' => 'Gunakan gaya testimoni atau social proof jika maklumat review tersedia.',
        'aida' => 'Ikut struktur Attention, Interest, Desire, Action.',
        'pas' => 'Ikut struktur Problem, Agitate, Solution.',
        'question' => 'Mulakan dengan soalan yang menarik perhatian.'
    );

    $length_rule = array('short' => 'Tulis pendek dan padat.', 'medium' => 'Tulis sederhana panjang.', 'long' => 'Tulis lebih panjang, sesuai untuk Facebook.');
    $emoji_rule = array('none' => 'Jangan guna emoji.', 'low' => 'Guna emoji sedikit sahaja.', 'medium' => 'Guna emoji secara sederhana.', 'high' => 'Guna emoji agak banyak tetapi masih kemas.');

    $cta_url = $data['product_url'];
    if ($cta_type === 'whatsapp' && !empty($data['whatsapp_url'])) $cta_url = $data['whatsapp_url'];

    return "Hasilkan satu marketing post untuk platform {$platform}.

TEMPLATE: {$template} - " . ($template_rules[$template] ?? '') . "
PANJANG: {$length} - " . ($length_rule[$length] ?? '') . "
EMOJI: {$emoji} - " . ($emoji_rule[$emoji] ?? '') . "
CTA TYPE: {$cta_type}

MAKLUMAT PRODUK:
Nama: {$data['name']}
Harga asal: {$data['normal_price']}
Harga promosi: {$data['promo_price']}
Link CTA: {$cta_url}
Deskripsi: {$data['short_desc']}

USP / Kelebihan:
{$data['usp']}

Pain Points:
{$data['pain_points']}

Target pembeli:
{$data['target_audience']}

FAQ:
{$data['faq']}

Review/Testimoni:
{$data['reviews']}

Hashtag:
{$data['hashtags']}

CTA:
{$data['cta']}

Nota:
{$data['notes']}

ANGLE KEMPEN:
{$angle}

ARAHAN TAMBAHAN:
{$extra}

ARAHAN PENULISAN:
- Tulis dalam BM Malaysia.
- Jika sesuai, mulakan dengan 'Sahabat-sahabat,'.
- Ton berilmu, jelas, mesra, meyakinkan.
- Promosi mesti disambung dengan ilmu secara natural.
- Jangan overclaim.
- Letakkan CTA dan link di hujung.
- Jika Instagram, sertakan hashtag.
- Jangan gunakan format markdown tebal (**).
";
}
