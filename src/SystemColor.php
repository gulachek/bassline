<?php

namespace Shell;

// https://www.w3.org/TR/css-color-4/#css-system-colors
class SystemColor
{
	// Background of application content or documents.
	const CANVAS = 'Canvas';

	// Text in application content or documents.
	const CANVAS_TEXT = 'CanvasText';

	// Text in non-active, non-visited links. For light backgrounds, traditionally blue.
	const LINK_TEXT = 'LinkText';

	// Text in visited links. For light backgrounds, traditionally purple.
	const VISITED_TEXT = 'VisitedText';

	// Text in active links. For light backgrounds, traditionally red.
	const ACTIVE_TEXT = 'ActiveText';

	// The face background color for push buttons.
	const BUTTON_FACE = 'ButtonFace';

	// Text on push buttons.
	const BUTTON_TEXT = 'ButtonText';

	// The base border color for push buttons.
	const BUTTON_BORDER = 'ButtonBorder';

	// Background of input fields.
	const FIELD = 'Field';

	// Text in input fields.
	const FIELD_TEXT = 'FieldText';

	// Background of selected text, for example from ::selection.
	const HIGHLIGHT = 'Highlight';

	// Text of selected text.
	const HIGHLIGHT_TEXT = 'HighlightText';

	// Background of selected items, for example a selected checkbox.
	const SELECTED_ITEM = 'SelectedItem';

	// Text of selected items.
	const SELECTED_ITEM_TEXT = 'SelectedItemText';

	// Background of text that has been specially marked (such as by the HTML mark element).
	const MARK = 'Mark';

	// Text that has been specially marked (such as by the HTML mark element).
	const MARK_TEXT = 'MarkText';

	// Disabled text. (Often, but not necessarily, gray.)
	const GRAY_TEXT = 'GrayText';

	// Background of accented user interface controls.
	const ACCENT_COLOR = 'AccentColor';

	// Text of accented user interface controls.
	const ACCENT_COLOR_TEXT = 'AccentColorText';
}
