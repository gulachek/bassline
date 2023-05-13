from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from .autosave import wait_save
from .page import Page

class GroupSelectPage(Page):
    @classmethod
    def fromDriver(cls, driver):
        wait_save(driver)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Select a group'), None)
        return None if mainHeading is None else GroupSelectPage(driver)

    def __init__(self, driver):
        self.driver = driver
        super().__init__(driver)

    def _findGroupBtn(self, groupname):
        btns = self.elems(By.CSS_SELECTOR, '.group-container button')
        return next((b for b in btns if b.text == groupname), None)


    def selectGroup(self, groupname):
        self._findGroupBtn(groupname).click()
        return GroupEditPage.fromDriver(self.driver)

    def hasGroupname(self, username):
        return self._findGroupBtn(username) is not None

    def createGroup(self):
        btn = self.elem(By.CSS_SELECTOR, 'button[value="Create"]')
        btn.click()
        return GroupEditPage.fromDriver(self.driver)

def edit_page_is_saved(driver):
    return driver.execute_script('return !("isBusy" in document.querySelector(".autosave").dataset)')

class GroupEditPage(Page):
    @classmethod
    def fromDriver(cls, driver):
        wait_save(driver)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Edit group'), None)
        return None if mainHeading is None else GroupEditPage(driver)

    def __init__(self, driver):
        self.driver = driver
        super().__init__(driver)

    def _groupnameInput(self):
        return self.elem(By.CSS_SELECTOR, 'input[type="text"]')

    def setGroupname(self, groupname):
        elem = self._groupnameInput()
        elem.clear()
        elem.send_keys(groupname)

    def groupname(self):
        return self._groupnameInput().get_attribute('value')

    def _capSelector(self, app, capName):
        return f"div[data-app=\"{app}\"] input[data-capability=\"{capName}\"]"

    def selectApp(self, app):
        select = Select(self.elem(By.TAG_NAME, 'select'))
        select.select_by_visible_text(app)

    def hasSecurity(self, app, capName):
        self.selectApp(app)
        sel = self._capSelector(app, capName)
        return self.driver.execute_script(f"return !!document.querySelector('{sel}').checked")

    def toggleSecurity(self, app, capName):
        self.selectApp(app)
        cbox = self.elem(By.CSS_SELECTOR, self._capSelector(app, capName))
        cbox.click()

    def waitSave(self):
        wait_save(self.driver)
