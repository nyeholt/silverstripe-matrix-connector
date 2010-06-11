<!-- 
This is just a sample template for a standard_page asset in Matrix

To add more, create templates for

- ExternalContent_MatrixContentItem_asset_type_code.ss
- ExternalContent_MatrixContentItem (to catch cases where a specific temlpate has been defined)
- ExternalContent (for catching any external content being viewed)

 -->
 
<h2>$Title</h2>

<% control Children %>
	<% if type_code == bodycopy %>
	<% control Children %>
		<% control Children %>
$html
		<% end_control %>
	<% end_control %>
	<% end_if %>
<% end_control %>  