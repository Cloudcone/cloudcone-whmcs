<?php
add_hook('ClientAreaPrimarySidebar', 1, function($primarySidebar) {
    if (!is_null($primarySidebar->getChild('Service Details Actions'))) {
        $actions_bar = $primarySidebar->getChild('Service Details Actions');
        $actions_bar->removeChild('Custom Module Button CCONE Rebuild');
        $actions_bar->removeChild('Custom Module Button CCONE Reset Root Password');
    }
});
