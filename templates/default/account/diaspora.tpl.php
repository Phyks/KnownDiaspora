<div class="row">

    <div class="span10 offset1">
        <?= $this->draw('account/menu') ?>
        <h1>Diaspora</h1>

    </div>

</div>
<div class="row">
    <div class="span10 offset1">
        <div class="row">
            <div class="span6">
                <p>
                    Easily share pictures, updates, and posts to Diaspora.</p>

                <p>
                    With Diaspora connected, you can cross-post content that you publish publicly on
                    your site.
                </p>
            </div>
        </div>
        <form class="form-horizontal" method="post" action="<?= \Idno\Core\site()->config()->getDisplayURL() ?>account/diaspora/">
            <div class="row">
                <div class="span2"><p><strong><label class="control-label" for="pod">Pod</label></strong></p></div>
                <div class="span4"><input type="text" name="pod" id="pod" value="<?=htmlspecialchars(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_pod'])?>"/></div>
                <div class="span4"><p class="config-desc">Your Diaspora pod.</p></div>
            </div>
            <div class="row">
                <div class="span2"><p><strong><label class="control-label" for="user">Username</label></strong></p></div>
                <div class="span4"><input type="text" name="user" id="user" value="<?=htmlspecialchars(\Idno\Core\site()->session()->currentUser()->diaspora['diaspora_username'])?>"/></div>
                <div class="span4"><p class="config-desc">Your Diaspora username.</p></div>
            </div>
            <div class="row">
                <div class="span2"><p><strong><label class="control-label" for="pass">Password</label></strong></p></div>
                <div class="span4"><input type="password" name="pass" id="pass"/></div>
                <div class="span4"><p class="config-desc">Your Diaspora password.</p></div>
            </div>
            <div class="control-group">
                <div class="controls-save">
                    <button type="submit" class="btn btn-primary">Save updates</button>
                </div>
            </div>
            <?= \Idno\Core\site()->actions()->signForm('/account/diaspora/')?>
        </form>
        <p><a href="<?= \Idno\Core\site()->config()->getDisplayURL() ?>account/diaspora/?remove">Remove</a> your credentials.</p>
    </div>
</div>
