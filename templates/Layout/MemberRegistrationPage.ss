<div id="HeaderContainer">
    <h1 class="page-header">$Title</h1>
</div>

<% if $Content %>
<div id="ContentContainer" class="typography clearfix">
    $Content
</div>
<% end_if %>

<div class="row">
    <div class="col-sm-4">
        <h2 class="page-header"><i class="fa fa-sign-in"></i> <%t MemberRegistrationPage.LoginFormHeader 'Sign In' %></h2>
        $LoginForm
    </div>
    <div class="col-sm-8">
        <h2 class="page-header"><i class="fa fa-pencil"></i> <%t MemberRegistrationPage.RegistrationFormHeader 'Sign Up' %></h2>
        <div id="PageContainer">
            <div id="FormContainer">$Form</div>
        </div>
    </div>
</div>