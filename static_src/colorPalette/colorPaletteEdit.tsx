import * as React from 'react';
import {
	useReducer,
	createContext,
	useContext,
	useCallback,
	ChangeEvent
} from 'react';

import { renderReactPage } from '../renderReactPage';
import { postJson } from '../postJson';
import { AutoSaveForm } from '../autosave/AutoSaveForm';

interface IPaletteColor
{
	id: number;
	name: string;
	hex: string;
}

type JsonMap<TValue> = { [key: string]: TValue };

interface IEditableMap<TValue>
{
	items: { [key: string]: TValue };
	deletedItems: string[];
	newItems: { [tempKey: string]: TValue };
}

interface IPalette
{
	id: number;
	name: string;
	colors: JsonMap<IPaletteColor>;
}

interface IPaletteEdit
{
	id: number;
	name: string;
	colors: IEditableMap<IPaletteColor>;
}

interface IPageModel
{
	palette: IPalette;
}

interface IEditState
{
	palette: IPaletteEdit;
	savedPalette: IPalette;
	isSaving: boolean;
	tempIdCounter: number;
}

const PaletteDispatchContext = createContext(null);

interface ISetNameAction
{
	type: 'setName';
	value: string;
}

interface IBeginSaveAction
{
	type: 'beginSave';
}

interface ISaveResponse
{
	error?: string|null;
	mappedColors: JsonMap<number>; // tempId -> id after actually creating
}

interface IEndSaveAction
{
	type: 'endSave';
	response: ISaveResponse;
	request: IPaletteEdit;
}

interface ISetColorNameAction
{
	type: 'setColorName';
	id: string;
	name: string;
}

interface ISetColorHexAction
{
	type: 'setColorHex';
	id: string;
	hex: string;
}

interface IAddColorAction
{
	type: 'addColor';
}

type EditAction = ISetNameAction
	| IBeginSaveAction
	| IEndSaveAction
	| ISetColorNameAction
	| ISetColorHexAction
	| IAddColorAction
;

function reducer(state: IEditState, action: EditAction)
{
	const palette = {...state.palette};
	let savedPalette = { ...state.savedPalette };
	let { isSaving, tempIdCounter } = state;

	if (action.type === 'setName')
	{
		palette.name = action.value;
	}
	else if (action.type === 'beginSave')
	{
		isSaving = true;
	}
	else if (action.type === 'endSave')
	{
		const { request, response } = action;
		if (response.error)
		{
			console.error(response.error);
		}
		else
		{
			savedPalette.name = request.name;

			const { items, newItems } = palette.colors;

			for (const tempId in request.colors.newItems)
			{
				const mappedId = response.mappedColors[tempId];

				items[mappedId] = newItems[tempId];
				delete newItems[tempId]; 

				request.colors.items[mappedId] = request.colors.newItems[tempId];
			}

			savedPalette.colors = request.colors.items;
		}

		isSaving = false;
	}
	else if (action.type === 'setColorName')
	{
		const { id, name } = action;
		const { items, newItems } = palette.colors;
		if (id in items) items[id].name = name;
		if (id in newItems) newItems[id].name = name;
	}
	else if (action.type === 'setColorHex')
	{
		const { id, hex } = action;
		const { items, newItems } = palette.colors;
		if (id in items) items[id].hex = hex;
		if (id in newItems) newItems[id].hex = hex;
	}
	else if (action.type === 'addColor')
	{
		const newColor = { id: -1, name: 'New Color', hex: '#000000' };
		const tempId = `temp${tempIdCounter++}`;
		palette.colors.newItems[tempId] = newColor;
	}

	return { palette, savedPalette, isSaving, tempIdCounter };
}

function paletteHasChange(edit: IPaletteEdit, saved: IPalette): boolean
{
	if (edit.name !== saved.name)
		return true;

	for (const id in edit.colors.items)
	{
		const editColor = edit.colors.items[id];
		const saveColor = saved.colors[id];
		if (editColor.name !== saveColor.name)
			return true;

		if (editColor.hex !== saveColor.hex)
			return true;
	}

	for (const tempId in edit.colors.newItems)
		return true;

	return false;
}

interface IPaletteNameProps
{
	name: string;
}

function PaletteName(props: IPaletteNameProps)
{
	const { name } = props;

	const dispatch = useContext(PaletteDispatchContext);

	const onChangeName = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setName', value: e.target.value });
	}, []);

	return <label> name:
		<input
			type="text"
			value={name}
			onChange={onChangeName}
			/>
	</label>;
}

interface IPalettePropertiesProps
{
	name: string;
}

function PaletteProperties(props: IPalettePropertiesProps)
{
	const { name } = props;

	return <section className="section">
		<h3> Palette properties </h3>
		<PaletteName name={name} />
	</section>;
}

interface IPaletteColorEditProps
{
	id: string;
	color: IPaletteColor;
}

function PaletteColorEdit(props: IPaletteColorEditProps)
{
	const { id, color } = props;
	const { name, hex } = color;
	const dispatch = useContext(PaletteDispatchContext);

	const changeName = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setColorName', id, name: e.target.value });
	}, [id]);

	const changeHex = useCallback((e: ChangeEvent<HTMLInputElement>) => {
		dispatch({ type: 'setColorHex', id, hex: e.target.value });
	}, [id]);

	return <div>
		<label> name: <input type="text" onChange={changeName} value={name} /> </label>
		<label> color: <input type="color" onChange={changeHex} value={hex} /> </label>
	</div>;
}

interface IPaletteColorsProperties
{
	colors: IEditableMap<IPaletteColor>;
}

function PaletteColors(props: IPaletteColorsProperties)
{
	const { colors } = props;
	const { items, newItems, deletedItems } = colors;

	const dispatch = useContext(PaletteDispatchContext);

	const addColor = useCallback(() => {
		dispatch({ type: 'addColor' });
	}, []);

	const colorEdits = [];
	for (const id in items)
	{
		colorEdits.push(<PaletteColorEdit key={id} id={id} color={items[id]} />);
	}

	for (const id in newItems)
	{
		colorEdits.push(<PaletteColorEdit key={id} id={id} color={newItems[id]} />);
	}

	return <section className="section">
		<h3> Colors </h3>
		<div> <button onClick={addColor}> New Color </button> </div>
		{colorEdits}
	</section>;
}

function Page(props: IPageModel)
{
	const colors = props.palette.colors;

	const initialState: IEditState = {
		palette: {
			name: props.palette.name,
			id: props.palette.id,
			colors: {
				newItems: {},
				deletedItems: [],
				items: Array.isArray(colors) ? {} : structuredClone(colors)
			}
		},
		savedPalette: props.palette,
		isSaving: false,
		tempIdCounter: 1
	};

	const [state, dispatch] = useReducer(reducer, initialState);

	const { isSaving, palette, savedPalette } = state;

	const hasChange = paletteHasChange(palette, savedPalette);

	const onSave = useCallback(async () => {
		dispatch({ type: 'beginSave' });

		const request = structuredClone(palette);

		const response = await postJson<ISaveResponse>('./save', { body: request });

		dispatch({ type: 'endSave', response, request });
	}, [palette]);

	return <AutoSaveForm onSave={onSave} hasChange={hasChange}>
		<PaletteDispatchContext.Provider value={dispatch}>
			<div className="header">
				<h1> Edit color palette </h1>
				<p className="save-indicator">
					<input type="checkbox" readOnly checked={!hasChange} /> 
					Saved
				</p>
			</div>

			<div className="section-container">
				<PaletteProperties name={palette.name} />
				<PaletteColors colors={palette.colors} />
			</div>
		</PaletteDispatchContext.Provider>
	</AutoSaveForm>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
