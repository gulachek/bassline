from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from selenium.webdriver.support.wait import WebDriverWait

class UserSelectPage:
    @classmethod
    def fromDriver(cls, driver):
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
    return driver.execute_script('return !("isBusy" in document.querySelector(".autosave").dataset)')

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

    def waitSave(self):
        WebDriverWait(self.driver, timeout=10).until(edit_page_is_saved)
