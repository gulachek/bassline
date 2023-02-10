from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

class Site:
    def __init__(self, uri, driver):
        self.uri = uri
        self.driver = driver

    def gotoLoginPage(self):
        self.driver.get(f"{self.uri}/login/")

    def currentUsername(self):
        uname = self.driver.find_element(By.CLASS_NAME, 'username')
        return uname.text

    def logInAsUser(self, username):
        self.gotoLoginPage()

        # TODO: put this in a LoginPage class

        # Select proper tab
        s = 'document.querySelector(".tab-strip").activateTab("noauth");'
        self.driver.execute_script(s)

        # Submit user info
        tabItem = self.driver.find_element(By.CSS_SELECTOR, 'tab-item[key="noauth"]')

        select = Select(tabItem.find_element(By.TAG_NAME, 'select'))
        select.select_by_visible_text(username)
        tabItem.find_element(By.CSS_SELECTOR, 'input[type="submit"]').click()
