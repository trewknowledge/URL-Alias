const { registerPlugin } = wp.plugins;
import UrlAlias from './url-alias';

registerPlugin( 'tk-url-alias', { render: UrlAlias } );
