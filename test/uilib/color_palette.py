from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from .autosave import wait_save
from .page import Page

class ColorPaletteSelectPage(Page):
    @classmethod
    def fromDriver(cls, driver):
        wait_save(driver)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Select color palette'), None)
        return None if mainHeading is None else ColorPaletteSelectPage(driver)

    def __init__(self, driver):
        self.driver = driver
        super().__init__(driver)

    def _paletteSelect(self):
        return Select(self.elem(By.TAG_NAME, 'select'))

    def hasPalette(self, name):
        for opt in self._paletteSelect().options:
            if opt.text == name:
                return True
        return False

    def editPalette(self, name):
        self._paletteSelect().select_by_visible_text(name)
        self.elem('input[value="Edit"]').click()

    def enterPaletteName(self, name):
        elem = self.elem(By.CSS_SELECTOR, 'input[type="text"]')
        elem.clear()
        elem.send_keys(name)

    def createPalette(self, name):
        self.enterPaletteName(name)
        btn = self.elem(By.CSS_SELECTOR, 'input[value="Create"]')
        btn.click()
        return ColorPaletteEditPage.fromDriver(self.driver)

class ColorPaletteEditPage(Page):
    @classmethod
    def fromDriver(cls, driver):
        wait_save(driver)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Edit color palette'), None)
        return None if mainHeading is None else ColorPaletteEditPage(driver)

    def __init__(self, driver):
        self.driver = driver
        super().__init__(driver)

    def _paletteNameInput(self):
        return self.elem(By.CLASS_NAME, 'palette-name')

    def setPaletteName(self, name):
        elem = self._paletteNameInput()
        elem.clear()
        elem.send_keys(name)

    def paletteName(self):
        return self._paletteNameInput().get_attribute('value')

    def _colorIndicators(self):
        return self.driver.find_elements(By.CLASS_NAME, 'color-indicator')

    def colors(self):
        elems = self._colorIndicators()
        colors = dict()
        for elem in elems:
            colors[elem.get_attribute('data-name')] = elem.get_attribute('data-hex')
        return colors

    def _colorElem(self, name):
        for elem in self._colorIndicators():
            if elem.get_attribute('data-name') == name:
                return elem
        return None

    def _selectColor(self, name):
        elem = self._colorElem(name)
        if elem is None:
            raise Exception(f"no color with name {name}")

        elem.click()

    def _setCurrentColorName(self, name):
        elem = self.elem(By.CLASS_NAME, 'current-color-name')
        elem.clear()
        elem.send_keys(name)

    def _setCurrentColorHex(self, hexval):
        self.driver.execute_script(f"window._setPaletteColorHex('{hexval}');")

    def setColor(self, currentName, *, name, color):
        self._selectColor(currentName)
        self._setCurrentColorName(name)
        self._setCurrentColorHex(color)

    def addColor(self, *, name, color):
        btn = self.elem(By.CLASS_NAME, 'add-color')
        btn.click()
        self._setCurrentColorName(name)
        self._setCurrentColorHex(color)

    def deleteColor(self, name):
        self._selectColor(name)
        btn = self.elem(By.CLASS_NAME, 'del-color')
        btn.click()

    def waitSave(self):
        wait_save(self.driver)
