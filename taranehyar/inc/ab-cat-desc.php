<?php
/**
 * ترانه یار ۳.۵.۲ — توضیح کوتاه و کاملِ دسته‌بندی در فرانت
 * ==========================================================================
 * کوتاه: بالای آرشیو، در همان کادرِ `archive_txt_box` که نسخهٔ مبنا استفاده می‌کرد.
 *         اگر فیلد تازه خالی باشد، به توضیحِ خودِ دسته برمی‌گردد ⇒ هیچ محتوای
 *         قبلی‌ای از دست نمی‌رود و ظاهرِ نسخهٔ مبنا عوض نمی‌شود.
 * کامل:  پایین آرشیو، با «نمایش بیشتر/کمتر».
 *
 * چطور بدون JavaScript هم درست است؟
 *   متنِ کامل همیشه در HTML هست و کلاسِ جمع‌شده فقط با JS اضافه می‌شود؛
 *   دکمه هم در HTML با `hidden` می‌آید و JS آن را نشان می‌دهد. پس:
 *     • خزندهٔ گوگل و کاربرِ بدون JS ⇒ کل متن را می‌بیند،
 *     • کاربرِ با JS ⇒ فقط بخشی، با دکمهٔ استاندارد (aria-expanded/aria-controls).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'tara_category_term' ) ) {
	function tara_category_term( $term = null ) {
		if ( $term instanceof WP_Term ) {
			return $term;
		}
		/* شناسه یا slug هم پذیرفته می‌شود (همان قراردادِ بقیهٔ توابعِ این نسخه) */
		if ( ( is_numeric( $term ) || ( is_string( $term ) && '' !== $term ) ) && function_exists( 'tara_term' ) ) {
			$found = tara_term( $term, 'category' );
			if ( $found ) {
				return $found;
			}
		}
		if ( is_category() ) {
			$obj = get_queried_object();
			if ( $obj instanceof WP_Term ) {
				return $obj;
			}
		}
		return null;
	}
}

if ( ! function_exists( 'tara_category_desc_short_text' ) ) {
	/** متنِ خامِ توضیح کوتاه (با fallback به توضیح دسته). */
	function tara_category_desc_short_text( $term = null ) {
		$term = tara_category_term( $term );
		if ( ! $term ) {
			return '';
		}
		$short = trim( (string) get_term_meta( $term->term_id, 'ab_desc_short', true ) );
		if ( '' !== $short ) {
			return $short;
		}
		/* سازگاری با نسخهٔ مبنا: همان category_description() قبلی */
		return trim( (string) term_description( $term->term_id, $term->taxonomy ) );
	}
}

if ( ! function_exists( 'tara_category_desc_full_html' ) ) {
	/** متنِ کاملِ پاک‌سازی‌شده (HTML مجازِ نوشته) یا رشتهٔ خالی. */
	function tara_category_desc_full_html( $term = null ) {
		$term = tara_category_term( $term );
		if ( ! $term ) {
			return '';
		}
		$full = (string) get_term_meta( $term->term_id, 'ab_desc_full', true );
		if ( '' === trim( wp_strip_all_tags( $full ) ) ) {
			return '';
		}
		return wp_kses_post( $full );
	}
}

if ( ! function_exists( 'tara_category_desc_short' ) ) {
	/** خروجیِ آمادهٔ بالای آرشیو. */
	function tara_category_desc_short( $term = null ) {
		$term = tara_category_term( $term );
		if ( ! $term ) {
			return '';
		}
		$text = tara_category_desc_short_text( $term );
		if ( '' === trim( wp_strip_all_tags( $text ) ) ) {
			return '';
		}
		$is_plain = ( false === strpos( $text, '<' ) );
		$inner    = $is_plain ? esc_html( $text ) : wp_kses_post( $text );
		return '<div class="archive_txt_box ab-cat-intro" data-ab-cat-intro>' . $inner . '<div class="clear"></div></div>';
	}
}

if ( ! function_exists( 'tara_category_desc_full' ) ) {
	/** خروجیِ آمادهٔ پایین آرشیو، با دکمهٔ نمایش بیشتر/کمتر. */
	function tara_category_desc_full( $term = null ) {
		static $n = 0;
		$html = tara_category_desc_full_html( $term );
		if ( '' === $html ) {
			return '';
		}
		$n++;
		$id = 'ab-cat-desc-' . $n;

		/* v3.7.8 — تیتر پویا: «درباره {نام دسته}» به‌جای «درباره این دسته».
		   عنوان دسته از خودِ ترم خوانده می‌شود؛ اگر ترم در دسترس نبود،
		   همان عبارت عمومی می‌ماند تا هیچ صفحه‌ای تیتر خالی نگیرد. */
		$term_obj = tara_category_term( $term );
		$title    = ( $term_obj && '' !== trim( (string) $term_obj->name ) )
			? 'درباره ' . $term_obj->name
			: 'درباره این دسته';

		return '<section class="ab-cat-desc" aria-labelledby="' . esc_attr( $id . '-title' ) . '" data-ab-desc>'
			. '<h2 class="ab-cat-desc__title" id="' . esc_attr( $id . '-title' ) . '">' . esc_html( $title ) . '</h2>'
			. '<div class="ab-cat-desc__body" id="' . esc_attr( $id ) . '" data-ab-desc-body>' . $html . '</div>'
			. '<p class="ab-cat-desc__actions">'
			. '<button type="button" class="ab-cat-desc__toggle" data-ab-desc-toggle '
			. 'aria-expanded="true" aria-controls="' . esc_attr( $id ) . '" hidden>نمایش بیشتر</button>'
			. '</p>'
			. '</section>';
	}
}

if ( ! function_exists( 'tara_category_desc_has_full' ) ) {
	function tara_category_desc_has_full( $term = null ) {
		return ( '' !== tara_category_desc_full_html( $term ) );
	}
}
