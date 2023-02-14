from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select

class LoginPage:
    def __init__(self, driver):
        self.driver = driver

    def logInAsUser(self, username):
        # Select proper tab
        s = 'document.querySelector(".tab-strip").activateTab("noauth");'
        self.driver.execute_script(s)

        # Submit user info
        tabItem = self.driver.find_element(By.CSS_SELECTOR, 'tab-item[key="noauth"]')

        select = Select(tabItem.find_element(By.TAG_NAME, 'select'))
        select.select_by_visible_text(username)
        tabItem.find_element(By.CSS_SELECTOR, 'input[type="submit"]').click()
