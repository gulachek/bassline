import * as React from 'react';
import {
	useState
} from 'react';

import { renderReactPage } from './renderReactPage';

interface IGroup
{
	id: number;
	groupname: string;
}

interface IPageModel
{
	group: IGroup;
}

function Page(props: IPageModel)
{
	const { group } = props;
	return <h1> Hello {group.groupname} </h1>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
