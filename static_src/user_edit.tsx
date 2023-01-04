import { createRoot } from 'react-dom/client';
import * as React from 'react';
import { useState, useCallback, MouseEvent } from 'react';

interface IAppProps
{
	hello: string;
}

function App(props: IAppProps)
{
	const [count, setCount] = useState(1);
	const onClick = useCallback((e: MouseEvent) => {
			setCount(count+1);
			e.preventDefault();
	}, [count, setCount]);

	const { hello } = props;
	return <div>
		<h3> Hello {hello} </h3>
		<p>
			Count is {count} <br />
			<button onClick={onClick}> Increment </button>
		</p>
	</div>;
}

window.addEventListener('DOMContentLoaded', () => {
	const data = document.getElementById('test-react-data');
	const parsedData = JSON.parse(data.innerText) as IAppProps;
	const app = document.getElementById('test-react');
	const root = createRoot(app);
	root.render(<App {...parsedData} />);
});
