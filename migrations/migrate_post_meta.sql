insert ignore into wp_lb_crawler_post_meta (post_id, meta_key, meta_value)
select post_id, meta_key, meta_value
from wp_postmeta
where meta_key like '_tradeinn_variation_id_%' or meta_key like '_tradeinn_product_id_%' or meta_key = '_tradeinn_props' or meta_key like '_tradeinn_attachment_%';