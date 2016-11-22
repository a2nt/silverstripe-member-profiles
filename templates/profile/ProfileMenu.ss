<% if $ProfileMenu %>
<nav class="navbar <% if $AddClass %>$AddClass<% else %>navbar-default<% end_if %>">
	<div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#ProfileMenuCollapse">
            <span class="sr-only"><%t Page.TOGGLENAVIGATION 'Toggle navigation' %></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
        </button>
    </div>
	<div id="ProfileMenuCollapse" class="collapse navbar-collapse">
		<ul class="nav navbar-nav">
			<% loop $ProfileMenu %>
				<li class="$ClassName $Status">
					<a href="$Link" rel="nofollow">
                        $Icon
						$Title
					</a>
				</li>
			<% end_loop %>
            <li class="pull-right">
                <a href="/Security/logout/">
                    <i class="fa fa-sign-out"></i>
                    <%t Member.LOGOUT 'Log Out' %>
                </a>
            </li>
		</ul>
	</div>
</nav>
<% end_if %>