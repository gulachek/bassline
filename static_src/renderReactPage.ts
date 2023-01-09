import { createRoot } from 'react-dom/client';
import * as React from 'react';

type renderer<TModel> = (model: TModel) => React.ReactNode;

export function renderReactPage<TModel>(renderer: renderer<TModel>)
{
	window.addEventListener('DOMContentLoaded', () => {
		const modelElem = document.getElementById('page-model');
		const model = JSON.parse(modelElem.innerText) as TModel;

		const app = document.getElementById('page-view');

		const reactRoot = createRoot(app);

		reactRoot.render(renderer(model));
	});
}

