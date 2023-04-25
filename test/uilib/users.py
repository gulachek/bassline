from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.support.wait import WebDriverWait

class UserSelectPage:
    @classmethod
    def fromDriver(cls, driver):
        WebDriverWait(driver, timeout=10).until(edit_page_is_saved)
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Select a user'), None)
        return None if mainHeading is None else UserSelectPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _findUserLink(self, username):
        links = self.driver.find_elements(By.LINK_TEXT, username)
        return links[0] if len(links) > 0 else None

    def selectUser(self, username):
        self._findUserLink(username).click()

    def hasUsername(self, username):
        return self._findUserLink(username) != None

    def enterUsername(self, username):
        uname = self.driver.find_element(By.CSS_SELECTOR, 'input[type="text"]')
        uname.clear()
        uname.send_keys(username)

    def selectGroup(self, groupname):
        select = Select(self.driver.find_element(By.TAG_NAME, 'select'))
        select.select_by_visible_text(groupname)

    def createUser(self, username, groupname):
        self.enterUsername(username)
        self.selectGroup(groupname)
        btn = self.driver.find_element(By.CSS_SELECTOR, 'input[value="Create"]')
        btn.click()
        return UserEditPage.fromDriver(self.driver)

def edit_page_is_saved(driver):
    return driver.execute_script('return !("isBusy" in (document.querySelector(".autosave")?.dataset || {}))')

class UserEditPage:
    @classmethod
    def fromDriver(cls, driver):
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Edit User'), None)
        return None if mainHeading is None else UserEditPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _usernameInput(self):
        return self.driver.find_element(By.CSS_SELECTOR, 'input[name="username"]')

    def setUsername(self, username):
        elem = self._usernameInput()
        elem.clear()
        elem.send_keys(username)

    def username(self):
        return self._usernameInput().get_attribute('value')

    def _primaryGroupSelect(self):
        return Select(self.driver.find_element(By.TAG_NAME, 'select'))
    def primaryGroup(self):
        select = self._primaryGroupSelect()
        return select.all_selected_options[0].text

    def setPrimaryGroup(self, groupname):
        select = self._primaryGroupSelect()
        select.select_by_visible_text(groupname)

    def _groupCbox(self, groupname):
        return self.driver.find_element(By.CSS_SELECTOR, f"input[data-groupname=\"{groupname}\"]")

    def _groupCboxIsChecked(self, groupname):
        return self.driver.execute_script(f"return !!document.querySelector('input[data-groupname=\"{groupname}\"]').checked")

    def toggleGroup(self, groupname):
        self._groupCbox(groupname).click()

    def isGroupMember(self, groupname):
        return self._groupCboxIsChecked(groupname)

    def _emailButtons(self):
        return self.driver.find_elements(By.CSS_SELECTOR, '.siwg .array button')

    def _siwgBtn(self, text):
        btns = self.driver.find_elements(By.CSS_SELECTOR, '.siwg button')
        return next((b for b in btns if b.text == text), None)

    def typeEmail(self, email):
        elem = self.driver.find_element(By.CSS_SELECTOR, 'input[type="email"]')
        elem.clear()
        elem.send_keys(email)

    def emails(self):
        return {b.text for b in self._emailButtons()}

    def addEmail(self, email):
        self._siwgBtn('+').click()
        self.typeEmail(email)

    def waitSave(self):
        WebDriverWait(self.driver, timeout=10).until(edit_page_is_saved)
