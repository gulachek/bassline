from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

class UserSelectPage:
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

    def createUser(self, username):
        self.enterUsername(username)
        btn = self.driver.find_element(By.CSS_SELECTOR, 'input[value="Create"]')
        btn.click()

