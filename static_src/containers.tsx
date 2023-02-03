import * as React from 'react';
import {
	useState,
	HTMLAttributes
} from 'react';

import './containers.scss';

// Render all children invisible and make one of them visible.
// Makes for better UX so that sizes are consistent w/in panels.
export function OneVisibleChild(props: HTMLAttributes<HTMLDivElement>)
{
	const copyProps = {...props};
	copyProps.className += ' one-visible-child';
	return <div {...copyProps}>
		{props.children}
	</div>;
}
