import * as React from 'react';
import {
	useReducer,
	createContext,
	useContext,
	useCallback,
	useState,
	useEffect,
	useRef,
	ChangeEvent
} from 'react';

import { renderReactPage } from '../renderReactPage';
import { postJson } from '../postJson';
import { AutoSaveForm } from '../autosave/AutoSaveForm';
import { SaveIndicator } from '../autosave/SaveIndicator';
import { SRGB } from '../srgb';
import { ErrorBanner } from '../ErrorBanner';
import { IInputField } from '../InputField';

import './colorPaletteEdit.scss';

interface IPaletteColor
{
	id: number;
	name: string;
	hex: string;
}

type JsonMap<TValue> = { [key: string]: TValue };
type InputChangeEvent = ChangeEvent<HTMLInputElement>;

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
	saveKey: string;
}

interface IPageModel
{
	palette: IPalette;
	initialSaveKey: string;
	nameField: IInputField;
}

interface IFieldValidity
{
	name: boolean;
	colors: Map<string, boolean>;
}

function fieldsAreValid(fields: IFieldValidity): boolean
{
	if (!fields.name)
		return false;

	for (const [id, isValid] of fields.colors)
		if (!isValid)
			return false;

	return true;
}

interface IEditState
{
	palette: IPaletteEdit;
	savedPalette: IPalette;
	isSaving: boolean;
	tempIdCounter: number;
	selectedColorId: string;
	errorMsg: string | null;
	fieldValidity: IFieldValidity;
}

const PaletteDispatchContext = createContext(null);

interface ISetNameAction
{
	type: 'setName';
	value: string;
	isValid: boolean;
}

interface IBeginSaveAction
{
	type: 'beginSave';
}

interface ISaveResponse
{
	error?: string|null;
	mappedColors: JsonMap<number>; // tempId -> id after actually creating
	newSaveKey: string;
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
	isValid: boolean;
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

interface IDeleteColorAction
{
	type: 'deleteColor';
	id: string;
}

interface ISelectColorAction
{
	type: 'selectColor';
	id: string;
}

type EditAction = ISetNameAction
	| IBeginSaveAction
	| IEndSaveAction
	| ISetColorNameAction
	| ISetColorHexAction
	| IAddColorAction
	| IDeleteColorAction
	| ISelectColorAction
;

function reducer(state: IEditState, action: EditAction)
{
	const palette = {...state.palette};
	let savedPalette = { ...state.savedPalette };
	let { isSaving, tempIdCounter, selectedColorId, errorMsg } = state;
	let fieldValidity = structuredClone(state.fieldValidity);

	if (action.type === 'setName')
	{
		palette.name = action.value;
		fieldValidity.name = action.isValid;
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
			errorMsg = response.error;
		}
		else
		{
			savedPalette.name = request.name;
			palette.saveKey = response.newSaveKey;

			const { items, newItems } = palette.colors;

			for (const tempId in request.colors.newItems)
			{
				const mappedId = response.mappedColors[tempId];

				items[mappedId] = newItems[tempId];
				delete newItems[tempId]; 
				fieldValidity.colors.set(`${mappedId}`, fieldValidity.colors.get(tempId));
				fieldValidity.colors.delete(tempId);

				if (tempId === selectedColorId)
					selectedColorId = `${mappedId}`;

				request.colors.items[mappedId] = request.colors.newItems[tempId];
			}

			const deletedItems = [];
			const actuallyDeleted = new Set(request.colors.deletedItems);
			for (const id of palette.colors.deletedItems)
			{
				if (!actuallyDeleted.has(id)) deletedItems.push(id);
			}
			palette.colors.deletedItems = deletedItems;

			savedPalette.colors = request.colors.items;
		}

		isSaving = false;
	}
	else if (action.type === 'setColorName')
	{
		const { id, name, isValid } = action;
		const { items, newItems } = palette.colors;
		if (id in items) items[id].name = name;
		if (id in newItems) newItems[id].name = name;
		fieldValidity.colors.set(id, isValid);
	}
	else if (action.type === 'setColorHex')
	{
		const { id, hex } = action;
		const { items, newItems } = palette.colors;
		if (id in items) items[id].hex = hex;
		if (id in newItems) newItems[id].hex = hex;
	}
	else if (action.type === 'selectColor')
	{
		selectedColorId = action.id;
	}
	else if (action.type === 'addColor')
	{
		const newColor = { id: -1, name: 'New Color', hex: '#000000' };
		const tempId = `temp${tempIdCounter++}`;
		palette.colors.newItems[tempId] = newColor;
		selectedColorId = tempId;
		fieldValidity.colors.set(tempId, true);
	}
	else if (action.type === 'deleteColor')
	{
		const { id } = action;
		const { items, newItems, deletedItems } = palette.colors;
		const allIds = [...Object.keys(items), ...Object.keys(newItems)];
		if (allIds.length > 1)
		{
			if (id === selectedColorId)
			{
				const index = allIds.indexOf(id);
				const prevIndex = Math.max(0, index - 1);
				selectedColorId = allIds[prevIndex];
			}

			if (id in items)
			{
				deletedItems.push(id);
				delete items[id];
			}
			if (id in newItems) delete newItems[id];
			fieldValidity.colors.delete(id);
		}
	}

	return {
		palette,
		savedPalette,
		isSaving,
		tempIdCounter,
		selectedColorId,
		errorMsg,
		fieldValidity
	};
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

	for (const id in edit.colors.deletedItems)
		return true;

	return false;
}

interface IPaletteNameProps
{
	name: string;
	field: IInputField;
}

function PaletteName(props: IPaletteNameProps)
{
	const { name, field } = props;

	const dispatch = useContext(PaletteDispatchContext);

	const onChangeName = useCallback((e: InputChangeEvent) => {
		dispatch({
			type: 'setName',
			value: e.target.value,
			isValid: e.target.reportValidity()
		});
	}, []);

	return <label> name:
		<input
			type="text"
			className="palette-name"
			value={name}
			onChange={onChangeName}
			{...field}
			/>
	</label>;
}

interface IPalettePropertiesProps
{
	name: string;
	nameField: IInputField;
}

function PaletteProperties(props: IPalettePropertiesProps)
{
	const { name, nameField } = props;

	return <section className="section">
		<h3> Palette properties </h3>
		<PaletteName name={name} field={nameField} />
	</section>;
}

interface IPaletteColorEditProps
{
	id: string;
	color: IPaletteColor;
	selected: boolean;
}

function PaletteColorEdit(props: IPaletteColorEditProps)
{
	const { id, color, selected } = props;
	const { name, hex } = color;

	const dispatch = useContext(PaletteDispatchContext);

	const elem = useRef<HTMLFieldSetElement>(null);

	useEffect(() => {
		const srgb = SRGB.fromHex(hex);
		const [h, s, l] = srgb.toHSL();
		const hStr = Math.round(h) + 'deg';
		const sStr = Math.round(100 * s) + '%';
		elem.current.style.setProperty('--hue', hStr);
		elem.current.style.setProperty('--saturation', sStr);
	});

	const select = useCallback(() => {
		dispatch({ type: 'selectColor', id });
	}, [id]);

	let className = 'color-indicator';
	if (selected) className += ' selected';

	return <fieldset 
		ref={elem}
		data-hex={hex}
		data-name={name}
		onClick={select}
		className={className}
		>
		<legend> {name} </legend>
	</fieldset>;
}

interface IPaletteColorsProperties
{
	colors: IEditableMap<IPaletteColor>;
	selectedId: string;
	nameField: IInputField;
}

function PaletteColors(props: IPaletteColorsProperties)
{
	const { colors, selectedId, nameField } = props;
	const { items, newItems, deletedItems } = colors;

	const dispatch = useContext(PaletteDispatchContext);

	const addColor = useCallback(() => {
		dispatch({ type: 'addColor' });
	}, []);

	const deleteColor = useCallback(() => {
		dispatch({ type: 'deleteColor', id: selectedId });
	}, [selectedId]);

	const ids = Object.keys(items);
	if (ids.length < 1)
		throw new Error('Expected at least one palette color');

	const colorEdits = [];
	let selectedColor: IPaletteColor | null = null;

	for (const id in items)
	{
		const selected = id === selectedId;
		if (selected) selectedColor = items[id];
		colorEdits.push(<PaletteColorEdit
			key={id} id={id} color={items[id]} selected={selected} />);
	}

	for (const id in newItems)
	{
		const selected = id === selectedId;
		if (selected) selectedColor = newItems[id];
		colorEdits.push(<PaletteColorEdit
			key={id} id={id} color={newItems[id]} selected={selected} />);
	}

	const nameRef = useRef<HTMLInputElement>();

	useEffect(() => {
		nameRef.current.reportValidity();
	});

	const setName = useCallback((e: InputChangeEvent) => {
		dispatch({
			type: 'setColorName',
			id: selectedId,
			name: e.target.value,
			isValid: e.target.reportValidity()
		});
	}, [selectedId]);

	const setHex = useCallback((e: InputChangeEvent) => {
		dispatch({ type: 'setColorHex', id: selectedId, hex: e.target.value });
	}, [selectedId]);

	useEffect(() => {
		(window as any)._setPaletteColorHex = (hex: string) => {
			dispatch({ type: 'setColorHex', id: selectedId, hex });
		};
	}, [selectedId]);

	return <section className="section">
		<h3> Colors </h3>
		<div> 
			<label> name:
			<input type="text" ref={nameRef}
				className="current-color-name"
				value={selectedColor.name}
				onChange={setName}
				{...nameField}
			/>
			</label>
			<label> color:
			<input type="color" value={selectedColor.hex} onChange={setHex} />
			</label>
			<button className="add-color" onClick={addColor}> + </button>
			<button className="del-color" onClick={deleteColor}> - </button>
		</div>
		{colorEdits}
	</section>;
}

function Page(props: IPageModel)
{
	const colors = props.palette.colors;
	const {
		nameField
	} = props;

	const initialState: IEditState = {
		palette: {
			name: props.palette.name,
			id: props.palette.id,
			saveKey: props.initialSaveKey,
			colors: {
				newItems: {},
				deletedItems: [],
				items: structuredClone(colors)
			}
		},
		savedPalette: props.palette,
		isSaving: false,
		tempIdCounter: 1,
		errorMsg: null,
		selectedColorId: Object.keys(colors)[0],
		fieldValidity: {
			name: true,
			colors: new Map<string,boolean>()
		}
	};

	const [state, dispatch] = useReducer(reducer, initialState);

	const {
		isSaving,
		palette,
		savedPalette,
		errorMsg,
		fieldValidity
	} = state;

	const hasChange = paletteHasChange(palette, savedPalette);

	const onSave = useCallback(async () => {
		dispatch({ type: 'beginSave' });

		const request = structuredClone(palette);

		const response = await postJson<ISaveResponse>('./save', { body: request });

		dispatch({ type: 'endSave', response, request });
	}, [palette]);

	const fieldsValid = fieldsAreValid(fieldValidity);
	const shouldSave = hasChange && !errorMsg && fieldsValid;

	return <div className="editor">
		<AutoSaveForm onSave={onSave} shouldSave={shouldSave && !isSaving} />
		{errorMsg && <ErrorBanner msg={errorMsg} />}
		<PaletteDispatchContext.Provider value={dispatch}>
			<div className="header">
				<h1> Edit color palette </h1>
			</div>

			<div className="section-container">
				<PaletteProperties name={palette.name} nameField={nameField} />
				<PaletteColors
					selectedId={state.selectedColorId}
					colors={palette.colors}
					nameField={nameField}
				/>
			</div>
			<p className="status-bar">
				<SaveIndicator
					isSaving={shouldSave || isSaving}
					hasError={!!errorMsg || !fieldsValid}
				/>
			</p>
		</PaletteDispatchContext.Provider>
	</div>;
}

renderReactPage<IPageModel>(model => <Page {...model} />);
