<div class="details">
    <% with $Member %>
        <h2>$Name</h2>
        <p><%t Member.CREATED 'Member Since' %>: $Created.Nice</p>
        <% if $Email %>
            <p><%t Member.EMAIL 'Email' %>: $Email</p>
        <% end_if %>
    <% end_with %>
</div>