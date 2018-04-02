<link href="modules/servers/cloudcone/css/client.css" rel="stylesheet">
<div class="product-details clearfix">
    <div class="row">
        <div class="col-md-6">
            <div class="product-status product-status-active">
                <div class="product-icon text-center">
                    <span class="fa-stack fa-lg">
                        <i class="fa fa-circle fa-stack-2x"></i>
                        <i class="fa fa-database fa-stack-1x fa-inverse"></i>
                    </span>
                    <h3>{$product}</h3>
                    <h4>{$groupname}</h4>
                </div>
                <div class="product-status-text">
                    {$status}
                </div>
            </div>

            <div class="row">
                <div class="col-xs-12">
                    <a href="clientarea.php?action=cancel&amp;id={$id}" class="btn btn-danger btn-block{if $pendingcancellation}disabled{/if}">
                        {if $pendingcancellation}
                            {$LANG.cancellationrequested}
                        {else}
                            {$LANG.clientareacancelrequestbutton}
                        {/if}
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6 text-center">
            <h4>{$LANG.clientareahostingregdate}</h4>
            {$regdate}

            <h4>{$LANG.recurringamount}</h4>
            {$recurringamount}

            <h4>{$LANG.orderbillingcycle}</h4>
            {$billingcycle}

            <h4>{$LANG.clientareahostingnextduedate}</h4>
            {$nextduedate}

            <h4>{$LANG.orderpaymentmethod}</h4>
            {$paymentmethod}

            <h4>{$LANG.ccone.status}</h4>
            {$instance.status|capitalize}
        </div>
    </div>

    <hr>
    <h3>{$LANG.ccone.clientarea_actions_title}</h3>
    <div class="ccone-server-actions">
        <div class="row">
            <div class="col-sm-4">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="modop" value="custom" />
                    <input type="hidden" name="a" value="Boot" />
                    <button type="submit" class="btn btn-info btn-fill btn-block">
                        {$LANG.ccone.clientarea_action_boot}
                    </button>
                </form>
            </div>
            <div class="col-sm-4">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="modop" value="custom" />
                    <input type="hidden" name="a" value="Reboot" />
                    <button type="submit" class="btn btn-info btn-fill btn-block">
                        {$LANG.ccone.clientarea_action_reboot}
                    </button>
                </form>
            </div>
            <div class="col-sm-4">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="modop" value="custom" />
                    <input type="hidden" name="a" value="Shutdown" />
                    <button type="submit" class="btn btn-info btn-fill btn-block">
                        {$LANG.ccone.clientarea_action_shutdown}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <hr>
    <h3>{$LANG.ccone.clientarea_details_title}</h3>
    <div class="ccone-server-info">
        <div class="row">
            <div class="col-md-6">
                <label>{$LANG.serverhostname}</label>
                <h4>{$instance.hostname}</h4>
            </div>
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_password}</label> <button id="root-pass-toggle" class="btn btn-default btn-xs">{$LANG.ccone.clientarea_password_toggle}</button>
                <h4 id="root-pass" class="hidden">{$instance.password}</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label>{$LANG.primaryIP}</label>
                <h4>{$instance.mainip}</h4>
            </div>
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_os}</label>
                <h4>{$instance.template}</h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_cpu}</label>
                <h4>{$instance.cpu} <small>{$LANG.ccone.clientarea_cpu_description}</small></h4>
            </div>
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_ram}</label>
                <h4>{$instance.ram} <small>{$LANG.ccone.clientarea_ram_description}</small></h4>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_bandwidth}</label>
                <div class="progress has-description">
                    <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow="{$instance.bandwidth.usage}" aria-valuemin="0" aria-valuemax="100" style="width: {$instance.bandwidth.usage}%;">
                        <span class="sr-only">{$instance.bandwidth.usage}% Used</span>
                    </div>
                </div>
                <small class="description text-muted">{$LANG.ccone.clientarea_bandwidth_description|sprintf:{$instance.bandwidth.used}:{$instance.bandwidth.total}}</small>
            </div>
            <div class="col-md-6">
                <label>{$LANG.ccone.clientarea_disk}</label>
                <h4>{$instance.disk.total} <small>{$LANG.ccone.clientarea_disk_description}</small></h4>
            </div>
        </div>
    </div>

    <hr>
    <div class="ccone-server-buttons">
        <div class="row">
            <div class="col-sm-3">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="customAction" value="graphs" />
                    <button type="submit" class="btn btn-default btn-fill btn-block">
                        <span class="fa fa-bar-chart"></span> {$LANG.ccone.clientarea_viewgraphs}
                    </button>
                </form>
            </div>
            <div class="col-sm-3">
                <button type="button" class="btn btn-default btn-fill btn-block" onClick="window.open('modules/servers/cloudcone/vnc.php?id={$serviceid}','_blank','width=800,height=600,status=no,location=no,toolbar=no,menubar=no')">
                    <span class="fa fa-terminal"></span> {$LANG.ccone.clientarea_viewconsole}
                </button>
            </div>
            <div class="col-sm-3">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="customAction" value="resetroot" />
                    <button type="submit" class="btn btn-warning btn-fill btn-block">
                        <span class="fa fa-warning"></span> {$LANG.ccone.clientarea_viewresetroot}
                    </button>
                </form>
            </div>
            <div class="col-sm-3">
                <form method="post" action="clientarea.php?action=productdetails">
                    <input type="hidden" name="id" value="{$serviceid}" />
                    <input type="hidden" name="customAction" value="rebuild" />
                    <button type="submit" class="btn btn-danger btn-fill btn-block">
                        <span class="fa fa-warning"></span> {$LANG.ccone.clientarea_viewrebuild}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
{literal}
<script>
    $('#root-pass-toggle').on('click', function() {
        $('#root-pass').toggleClass('hidden');
    });
</script>
{/literal}
