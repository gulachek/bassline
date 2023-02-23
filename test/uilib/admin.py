from selenium.webdriver.common.by import By

class AdminPage:
    def __init__(self, driver):
        self.driver = driver

    def _findSection(self, title):
        cards = self.driver.find_elements(By.CSS_SELECTOR, '.card h2')
        return next((c for c in cards if c.text == title), None)


    def _hasSection(self, title):
        return self._findSection(title) is not None

    def hasUsersSection(self):
        return self._hasSection('Users')

    def hasGroupsSection(self):
        return self._hasSection('Groups')
