insert ignore into wp_lb_crawler_term_meta (term_id, meta_key, meta_value)
select term_id, meta_key, meta_value
from wp_termmeta
where meta_key like '_tradeinn_term_name_%' or meta_key like '_tradeinn_term_id_%'