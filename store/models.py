class LoginAttempt:
    def __init__(self, user_id, timestamp, ip_address):
        self.user_id = user_id
        self.timestamp = timestamp
        self.ip_address = ip_address

class SuspiciousActivity:
    def __init__(self, user_id, activity_type, timestamp):
        self.user_id = user_id
        self.activity_type = activity_type
        self.timestamp = timestamp
