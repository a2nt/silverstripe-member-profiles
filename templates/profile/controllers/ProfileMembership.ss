<div id="ProfileMembership">
    <div class="row">
        <div class="details col-sm-6">
            <h2 class="page-header">
                <i class="fa fa-user"></i>
                $Member.Name
            </h2>
            <% with $Member %>
                <p><%t Member.CREATED 'Member Since' %>: $Created.Nice</p>
                <% if $Email %><p>Email: $Email</p><% end_if %>
            <% end_with %>
        </div>
        <div class="editform col-sm-6">
            <h2 class="page-header">
                <i class="fa fa-pencil-square-o"></i>
                <%t ProfilePage.MEMBEREDITPROFILEHEADER 'Edit Profile' %>
            </h2>
            $MemberEditProfileForm
        </div>
    </div>
</div>