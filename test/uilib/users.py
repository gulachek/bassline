from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

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

