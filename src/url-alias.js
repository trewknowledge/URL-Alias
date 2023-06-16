const { __ } = wp.i18n;
const { compose } = wp.compose;
const { withSelect, withDispatch } = wp.data;
const { TextControl, PanelRow } = wp.components;
const { PluginDocumentSettingPanel } = wp.editPost;

const UrlAlias = ( { postType, postMeta, setPostMeta } ) => {
	if ( ! window?.tkUrlAlias?.postTypes?.includes( postType ) ) {
		return;
	}

	return (
		<PluginDocumentSettingPanel
			name="tk-url-alias"
			title="URL Alias"
			className="tk-url-alias"
		>
			<PanelRow>
				<TextControl
					value={ postMeta?.tk_url_alias }
					onChange={ ( value ) => {
						setPostMeta( { tk_url_alias: value } );
					} }
					help={ __(
						'Specify an alternative path for your posts and pages',
						'tk-url-alias'
					) }
				/>
			</PanelRow>
		</PluginDocumentSettingPanel>
	);
};

export default compose( [
	withSelect( ( select ) => {
		return {
			postMeta: select( 'core/editor' ).getEditedPostAttribute( 'meta' ),
			postType: select( 'core/editor' ).getCurrentPostType(),
		};
	} ),
	withDispatch( ( dispatch ) => {
		return {
			setPostMeta( newMeta ) {
				dispatch( 'core/editor' ).editPost( { meta: newMeta } );
			},
		};
	} ),
] )( UrlAlias );
