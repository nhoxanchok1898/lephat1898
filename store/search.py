class ProductSearch:
    def __init__(self, products):
        self.products = products

    def search(self, query):
        return [product for product in self.products if query.lower() in product.lower()]

class SearchAnalytics:
    def __init__(self):
        self.search_data = []

    def log_search(self, query, results_count):
        self.search_data.append({'query': query, 'results_count': results_count})

    def get_search_data(self):
        return self.search_data