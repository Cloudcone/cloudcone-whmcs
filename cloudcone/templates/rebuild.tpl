<h2>
    <form method="post" action="clientarea.php?action=productdetails" style="display: inline-block;">
        <input type="hidden" name="id" value="{$serviceid}" />
        <button type="submit" class="btn btn-default">{$LANG.clientareabacklink}</button>
    </form>
    {$LANG.ccone.clientarea_rebuild_title}
</h2>

<br>

<div class="alert alert-info">
    {$LANG.ccone.clientarea_rebuild_notice}
</div>

<hr>

<form method="post" action="clientarea.php?action=productdetails">
    <div class="form-group">
        <label>Operating System</label>
        <select name="os" class="form-control">
            {foreach from=$oslist item=os}
                <option value="{$os.id}">{$os.name}</option>
            {/foreach}
        </select>
    </div>
    <div>
        <font color="red">
            <span class="fa fa-warning"></span> {$LANG.ccone.clientarea_rebuild_alert}
        </font>
    </div>
    <input type="hidden" name="id" value="{$serviceid}" />
    <input type="hidden" name="modop" value="custom" />
    <input type="hidden" name="customAction" value="rebuild" />
    <input type="hidden" name="a" value="Reinstall" />
    <button type="submit" class="btn btn-info btn-fill">
        {$LANG.ccone.clientarea_action_rebuild}
    </button>
</form>
<hr>
