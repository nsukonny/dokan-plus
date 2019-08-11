<?php

$seller_locations = isset($store_info['seller_locations']) &&
!empty($store_info['seller_locations']) ? $store_info['seller_locations'] : null;

if ($seller_locations) { ?>
    <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="headLocations">
            <h4 class="panel-title">
                <a class="collapsed" role="button" data-toggle="collapse"
                   data-parent="#storeaccordion" href="#collapseLocations" aria-expanded="false"
                   aria-controls="collapseLocations">
                    Locations
                </a>
            </h4>
        </div>
        <div id="collapseLocations" class="panel-collapse collapse" role="tabpanel"
             aria-labelledby="headLocations">
            <div class="panel-body">
                <?php echo nl2br(htmlspecialchars($seller_locations)); ?>
            </div>
        </div>
    </div>
<?php }