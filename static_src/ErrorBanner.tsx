import * as React from 'react';

import './ErrorBanner.scss';

interface IErrorBannerProps
{
	msg: string;
}

export function ErrorBanner(props: IErrorBannerProps)
{
	const { msg } = props;
	
	return <div className="error-banner">
		<p> {msg} </p>
	</div>;
}
