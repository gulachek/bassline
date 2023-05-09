from .find_elem import find_elem

class Page:
    def __init__(self, driver):
        self.driver = driver

    def elem(self, mode, selector):
        return find_elem(self.driver, mode, selector)

    def elems(self, mode, selector):
        return self.driver.find_elements(mode, selector)
