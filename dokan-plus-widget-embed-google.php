<?php

$seller_google = isset($store_info['seller_embed_google']) &&
!empty($store_info['seller_embed_google']) ? $store_info['seller_embed_google'] : null;

if ($seller_google) { ?>
    <iframe src="<?php echo $seller_google; ?>" width="100%" height="300"></iframe>
<?php }