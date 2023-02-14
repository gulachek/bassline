from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

from .login import LoginPage

class Site:
    def __init__(self, uri, driver):
        self.uri = uri
        self.driver = driver

    def gotoLoginPage(self):
        self.driver.get(f"{self.uri}/login/")
        return LoginPage(self.driver)

    def clickLoginLink(self):
        login = self.driver.find_elements(By.CLASS_NAME, 'login')
        assert len(login) > 0
        login[0].click()

    def gotoHelloPage(self):
        self.driver.get(f"{self.uri}/hello/")

    def currentUsername(self):
        unames = self.driver.find_elements(By.CLASS_NAME, 'username')
        return unames[0].text if len(unames) > 0 else None

    def logOut(self):
        self.driver.get(f"{self.uri}/logout/")

    def logInAsUser(self, username):
        if not self.driver.current_url.endswith('/login/'):
            self.gotoLoginPage()

        page = LoginPage(self.driver)
        page.logInAsUser(username)
