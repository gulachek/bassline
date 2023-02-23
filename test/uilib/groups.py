from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

class GroupSelectPage:
    @classmethod
    def fromDriver(cls, driver):
        h1s = driver.find_elements(By.TAG_NAME, 'h1')
        mainHeading = next((h for h in h1s if h.text == 'Select a group'), None)
        return None if mainHeading is None else GroupSelectPage(driver)

    def __init__(self, driver):
        self.driver = driver

    def _findGroupBtn(self, groupname):
        btns = self.driver.find_elements(By.CSS_SELECTOR, '.group-container button')
        return next((b for b in btns if b.text == groupname), None)


    def selectGroup(self, groupname):
        self._findGroupBtn(groupname).click()

    def hasGroupname(self, username):
        return self._findGroupBtn(username) is not None

    def enterGroupname(self, groupname):
        gname = self.driver.find_element(By.CSS_SELECTOR, 'input[type="text"]')
        gname.clear()
        gname.send_keys(groupname)

    def createGroup(self, groupname):
        self.enterGroupname(groupname)
        btn = self.driver.find_element(By.CSS_SELECTOR, 'input[value="Create"]')
        btn.click()

