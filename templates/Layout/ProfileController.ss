<div id="ProfileMenu">
    <% include ProfileMenu %>
</div>

<div id="HeaderContainer">
    <h1 class="page-header">
        $Icon
        $Title
    </h1>
</div>

<% if $Content %>
<div id="ContentContainer" class="typography clearfix">
    $Content
</div>
<% end_if %>

<div id="ProfileArea" class="$ProfileClass">
    <%-- It's $Layout like area, but for profile sub-areas --%>
    $ProfileArea
</div>