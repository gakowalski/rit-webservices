from suds.client import Client
from sslcontext import create_ssl_context, HTTPSTransport

class RIT_Webservices:
    user = ''
    password = ''
    instance = ''
    cert = ''

    instances = {
        'production': 'https://intrit.poland.travel/rit/integration/',
        'test': 'https://intrittest.poland.travel/rit/integration',
    }

    def __init__ (self, user, password, cert, instance = 'production'):
        self.user = user
        self.password = password
        self.instance = instance
        self.cert = cert

    def get_webservice(self, method_name):
        url = self.instances[self.instance] + method_name
        return Client(url + '?wsdl', location = url)

ws = RIT_Webservices('rotkujpom', 'x3Yq5UUU4ghMj6x8KD5mmBb6', 'rotkujpom.pem', 'test')
print ws
print ws.get_webservice('MetadataOfRIT')
