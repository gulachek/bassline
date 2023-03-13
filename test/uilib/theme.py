from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.support.wait import WebDriverWait

class ThemeSelectPage:
    @classmethod
    def fromDriver(cls, driver):
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Select theme'), None)
        return None if mainHeading is None else ThemeSelectPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _themeSelect(self):
        return Select(self.driver.find_element(By.TAG_NAME, 'select'))

    def hasTheme(self, name):
        for opt in self._themeSelect().options:
            if opt.text == name:
                return True
        return False

    def editTheme(self, name):
        self._themeSelect().select_by_visible_text(name)
        self.driver.find_element(By.CSS_SELECTOR, 'input[value="Edit"]').click()


    def createTheme(self):
        btn = self.driver.find_element(By.CSS_SELECTOR, 'input[value="Create"]')
        btn.click()
        return ThemeEditPage.fromDriver(self.driver)

def edit_page_is_saved(driver):
    return driver.execute_script('return !("isBusy" in document.querySelector(".autosave").dataset)')

class ThemeEditPage:
    @classmethod
    def fromDriver(cls, driver):
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Edit theme'), None)
        return None if mainHeading is None else ThemeEditPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _themeNameInput(self):
        return self.driver.find_element(By.CLASS_NAME, 'theme-name')

    def setThemeName(self, name):
        elem = self._themeNameInput()
        elem.clear()
        elem.send_keys(name)

    def changePalette(self, paletteName):
        btn = self.driver.find_element(By.CLASS_NAME, 'change-palette')
        btn.click()
        sel = Select(self.driver.find_element(By.CLASS_NAME, 'palette-select'))
        sel.select_by_visible_text(paletteName)
        submit = self.driver.find_element(By.CSS_SELECTOR, 'input[value="Change Palette"]')
        submit.click()


    def themeName(self):
        return self._themeNameInput().get_attribute('value')

    def _themeColorBtns(self):
        return self.driver.find_elements(By.CLASS_NAME, 'theme-color-edit')

    def themeColors(self):
        elems = self._themeColorBtns()
        colors = dict()
        for elem in elems:
            colors[elem.text] = {
                'fgName': elem.get_attribute('data-fg_color'),
                'fgLightness': float(elem.get_attribute('data-fg_lightness')),
                'bgName': elem.get_attribute('data-bg_color'),
                'bgLightness': float(elem.get_attribute('data-bg_lightness'))
            }
        return colors

    def _themeColorElem(self, name):
        for elem in self._themeColorBtns():
            if elem.text.endswith(name):
                return elem
        return None

    def _selectThemeColor(self, name):
        elem = self._themeColorElem(name)
        if elem is None:
            raise Exception(f"no color with name {name}")

        elem.click()

    def _setCurrentThemeColorName(self, name):
        elem = self.driver.find_element(By.CLASS_NAME, 'current-theme-color-name')
        elem.clear()
        elem.send_keys(name)

    def _setCurrentThemeColorLightness(self, fg, bg):
        self.driver.execute_script(f"window._setThemeColorLightness({fg}, {bg});")

    def _setThemeColorPaletteColor(self, fgbgStr, pColorName):
        elem = self.driver.find_element(By.CLASS_NAME, f"{fgbgStr}-editor")
        btns = elem.find_elements(By.TAG_NAME, 'button')
        for btn in btns:
            if btn.text.endswith(pColorName):
                btn.click()
                return
        input('waiting')
        raise Exception(f"no palette color with name {pColorName}")


    def setThemeColor(self, currentName, *, name, fgName, fgLightness, bgName, bgLightness):
        self._selectThemeColor(currentName)
        self._setCurrentThemeColorName(name)
        self._setCurrentThemeColorLightness(fgLightness, bgLightness)
        self._setThemeColorPaletteColor('fg', fgName)
        self._setThemeColorPaletteColor('bg', bgName)

    def addThemeColor(self, *, name, fgName, fgLightness, bgName, bgLightness):
        btn = self.driver.find_element(By.CLASS_NAME, 'add-color')
        btn.click()
        self._setCurrentThemeColorName(name)
        self._setCurrentThemeColorLightness(fgLightness, bgLightness)
        self._setThemeColorPaletteColor('fg', fgName)
        self._setThemeColorPaletteColor('bg', bgName)

    def deleteThemeColor(self, name):
        self._selectThemeColor(name)
        btn = self.driver.find_element(By.CLASS_NAME, 'del-color')
        btn.click()

    def _appSelectElem(self):
        return Select(self.driver.find_element(By.CLASS_NAME, 'app-select'))

    def mappings(self):
        apps = self._appSelectElem()
        mappings = dict()
        for opt in apps.options:
            appName = opt.text
            apps.select_by_visible_text(appName)
            mappings[appName] = dict()
            for e in self.driver.find_elements(By.CLASS_NAME, 'mapping-select'):
                mappingName = e.get_attribute('data-mapping-name')
                value = Select(e).all_selected_options[0].text
                mappings[appName][mappingName] = value
        return mappings

    def _selectApp(self, name):
        sel = self._appSelectElem()
        sel.select_by_visible_text(name)

    def _selectMapping(self, mappingName, themeColorName):
        sel = Select(self.driver.find_element(By.CSS_SELECTOR, f"select[data-mapping-name=\"{mappingName}\"]"))
        sel.select_by_visible_text(themeColorName)

    def mapColor(self, appName, mappingName, themeColorName):
        self._selectApp(appName)
        self._selectMapping(mappingName, themeColorName)

    def activeStatus(self):
        elems = self.driver.find_elements(By.CSS_SELECTOR, 'input[name="theme-status"]')
        for elem in elems:
            if elem.get_attribute('checked') is not None:
                return elem.get_attribute('value')
        raise Exception('no checked theme-status found')

    def setActiveStatus(self, status):
        elems = self.driver.find_elements(By.CSS_SELECTOR, 'input[name="theme-status"]')
        for elem in elems:
            if elem.get_attribute('value') == status:
                elem.click()
                return
        raise Exception(f"status '{status}' radio button not found")

    def waitSave(self):
        WebDriverWait(self.driver, timeout=10).until(edit_page_is_saved)
